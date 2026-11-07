<?php

namespace App\Services;

use App\Enums\LabReportStatus;
use App\Enums\LabRequestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Consultation;
use App\Models\LaboratoryReport;
use App\Models\LaboratoryRequest;
use App\Models\LaboratoryTest;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\LabReportUploaded;
use App\Notifications\LabTestRequested;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LaboratoryService
{
    public function __construct(private SequenceGenerator $sequences, private AuditLogger $audit) {}

    public function request(Patient $patient, array $data, User $doctor, ?Consultation $consultation = null): LaboratoryRequest
    {
        if ($consultation && $consultation->patient_id !== $patient->id) {
            throw new BusinessRuleException('Consultation does not belong to this patient.');
        }

        $test = ! empty($data['laboratory_test_id']) ? LaboratoryTest::query()->find($data['laboratory_test_id']) : null;

        $req = DB::transaction(function () use ($patient, $data, $doctor, $consultation, $test) {
            $req = new LaboratoryRequest([
                'laboratory_test_id' => $test?->id,
                'test_name' => $test?->name ?? $data['test_name'],
                'test_category' => $test?->category ?? ($data['test_category'] ?? null),
                'priority' => $data['priority'] ?? 'routine',
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'laboratory_name' => $data['laboratory_name'] ?? null,
                'consultation_id' => $consultation?->id,
                'visit_id' => $consultation?->visit_id ?? ($data['visit_id'] ?? null),
            ]);
            $req->patient_id = $patient->id;
            $req->request_number = $this->sequences->code('lab_request', 'LRQ');
            $req->status = LabRequestStatus::Requested;
            $req->requested_by = $doctor->id;
            $req->save();
            $this->audit->created($req, 'Requested laboratory test '.$req->test_name);

            return $req;
        });

        DB::afterCommit(function () use ($req) {
            $techs = User::query()->active()->permission('lab.upload')->get();
            Notification::send($techs, new LabTestRequested($req));
        });

        return $req;
    }

    public function changeStatus(LaboratoryRequest $req, LabRequestStatus $to, User $by, ?string $reason = null): LaboratoryRequest
    {
        if (! in_array($to, $req->allowedManualTransitions(), true)) {
            throw new BusinessRuleException("Cannot move request from {$req->status->label()} to {$to->label()}.");
        }
        if ($to === LabRequestStatus::Cancelled && blank($reason)) {
            throw new BusinessRuleException('A cancellation reason is required.');
        }

        return DB::transaction(function () use ($req, $to, $reason) {
            $from = $req->status;
            $req->status = $to;
            if ($to === LabRequestStatus::SampleCollected && ! $req->sample_collected_at) {
                $req->sample_collected_at = now();
            }
            if ($to === LabRequestStatus::Cancelled) {
                $req->cancellation_reason = $reason;
            }
            $req->save();
            $this->audit->log('status_changed', $req, "Lab request {$from->label()} → {$to->label()}", ['status' => ['old' => $from->value, 'new' => $to->value], 'reason' => $reason]);

            return $req;
        });
    }

    /** Store a report file on the PRIVATE disk and link it to patient/request/visit. */
    public function upload(Patient $patient, UploadedFile $file, array $data, User $by, ?LaboratoryRequest $req = null): LaboratoryReport
    {
        if ($req && $req->patient_id !== $patient->id) {
            throw new BusinessRuleException('Request does not belong to this patient.');
        }
        if ($req && in_array($req->status, [LabRequestStatus::Cancelled], true)) {
            throw new BusinessRuleException('Cannot upload a report against a cancelled request.');
        }

        $disk = config('clinic.lab_uploads.disk');
        $dir = config('clinic.lab_uploads.directory').'/'.$patient->id.'/'.now()->format('Y/m');
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $path = $file->storeAs($dir, Str::uuid().'.'.$ext, $disk);

        try {
            $report = DB::transaction(function () use ($patient, $file, $data, $by, $req, $disk, $path) {
                $report = new LaboratoryReport([
                    'laboratory_request_id' => $req?->id,
                    'visit_id' => $req?->visit_id ?? ($data['visit_id'] ?? null),
                    'consultation_id' => $req?->consultation_id ?? ($data['consultation_id'] ?? null),
                    'test_name' => $data['test_name'] ?? $req?->test_name,
                    'test_category' => $data['test_category'] ?? $req?->test_category,
                    'laboratory_name' => $data['laboratory_name'] ?? $req?->laboratory_name,
                    'test_date' => $data['test_date'] ?? null,
                    'report_date' => $data['report_date'] ?? now()->toDateString(),
                    'result_summary' => $data['result_summary'] ?? null,
                ]);
                $report->forceFill([
                    'report_number' => $this->sequences->code('lab_report', 'LRP'),
                    'patient_id' => $patient->id,
                    'status' => LabReportStatus::Completed,
                    'file_disk' => $disk,
                    'file_path' => $path,
                    'original_filename' => substr($file->getClientOriginalName(), 0, 255),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $by->id,
                ])->save();

                if ($req) {
                    $req->status = LabRequestStatus::Completed;
                    $req->completed_at = now();
                    $req->save();
                }

                $this->audit->log('uploaded', $report, 'Uploaded laboratory report '.$report->report_number, [
                    'test_name' => $report->test_name, 'file' => $report->original_filename, 'request_id' => $req?->id,
                ]);

                return $report;
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }

        DB::afterCommit(function () use ($report, $req) {
            $doctor = $req?->requester()->first();
            if ($doctor && $doctor->is_active) {
                $doctor->notify(new LabReportUploaded($report));
            }
        });

        return $report;
    }

    public function review(LaboratoryReport $report, User $doctor, ?string $notes): LaboratoryReport
    {
        if ($report->status === LabReportStatus::Reviewed) {
            throw new BusinessRuleException('This report has already been reviewed.');
        }

        return DB::transaction(function () use ($report, $doctor, $notes) {
            $report->forceFill([
                'status' => LabReportStatus::Reviewed,
                'reviewed_by' => $doctor->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ])->save();

            if ($req = $report->request()->first()) {
                $pending = $req->reports()->where('status', LabReportStatus::Completed->value)->exists();
                if (! $pending && $req->status === LabRequestStatus::Completed) {
                    $req->forceFill(['status' => LabRequestStatus::Reviewed])->save();
                }
            }

            $this->audit->log('reviewed', $report, 'Reviewed laboratory report '.$report->report_number, ['notes' => $notes]);

            return $report;
        });
    }
}
