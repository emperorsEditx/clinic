<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Enums\FollowUpStatus;
use App\Enums\VisitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Consultation;
use App\Models\ConsultationVitals;
use App\Models\FollowUp;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class ConsultationService
{
    public function __construct(
        private SequenceGenerator $sequences,
        private AuditLogger $audit,
        private PrescriptionService $prescriptions,
        private LaboratoryService $laboratory,
        private VisitService $visits,
    ) {}

    public function start(Visit $visit, User $doctor): Consultation
    {
        if (! $doctor->isDoctor()) {
            throw new BusinessRuleException('Only doctors can start consultations.');
        }
        if (in_array($visit->status, [VisitStatus::Completed, VisitStatus::Cancelled], true)) {
            throw new BusinessRuleException('This visit is closed. Record a new visit to start another consultation.');
        }

        $draft = $visit->consultations()->where('status', ConsultationStatus::Draft->value)->where('doctor_id', $doctor->id)->first();
        if ($draft) {
            return $draft;
        }

        return DB::transaction(function () use ($visit, $doctor) {
            $c = new Consultation;
            $c->forceFill([
                'consultation_number' => $this->sequences->code('consultation', 'CON'),
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'doctor_id' => $doctor->id,
                'symptoms' => $visit->chief_complaint,
                'status' => ConsultationStatus::Draft,
            ])->save();

            $visit->update(['status' => VisitStatus::InConsultation]);
            $this->audit->created($c, 'Started consultation '.$c->consultation_number);

            return $c;
        });
    }

    /**
     * Save a draft consultation (and optionally complete it) together with vitals,
     * the draft prescription and new lab requests — all in one transaction.
     */
    public function save(Consultation $c, array $data, User $doctor, bool $complete = false): Consultation
    {
        if (! $c->isDraft()) {
            throw new BusinessRuleException('Completed consultations cannot be edited. Use an amendment.');
        }
        if ($c->doctor_id !== $doctor->id) {
            throw new BusinessRuleException('Only the consulting doctor can edit this draft.');
        }
        if ($complete && blank($data['diagnosis'] ?? null)) {
            throw new BusinessRuleException('A diagnosis is required to complete the consultation.');
        }

        return DB::transaction(function () use ($c, $data, $doctor, $complete) {
            $c->fill(array_intersect_key($data, array_flip(Consultation::CLINICAL_FIELDS)));
            $c->save();

            $vitals = array_intersect_key($data['vitals'] ?? [], array_flip(ConsultationVitals::FIELDS));
            if (array_filter($vitals, fn ($v) => $v !== null && $v !== '') || $c->vitals) {
                $c->vitals()->updateOrCreate([], array_merge(array_fill_keys(ConsultationVitals::FIELDS, null), $vitals, ['recorded_by' => $doctor->id]));
            }

            // validated() rebuilds wildcard arrays in rule order; restore the doctor's row order first.
            $items = $data['items'] ?? [];
            ksort($items);
            $items = array_values(array_filter($items, fn ($i) => filled($i['medicine_name'] ?? null)));
            $existingDraft = $c->prescriptions()->where('status', 'draft')->first();
            if ($items || $existingDraft) {
                $rx = $existingDraft ?? $this->prescriptions->draftFor($c, $doctor);
                if ($items) {
                    $this->prescriptions->syncItems($rx, $items, [
                        'general_instructions' => $data['general_instructions'] ?? null,
                        'notes' => $data['prescription_notes'] ?? null,
                    ]);
                } else {
                    $this->prescriptions->discardDraft($rx);
                }
            }

            foreach ($data['lab_tests'] ?? [] as $test) {
                if (filled($test['test_name'] ?? null) || filled($test['laboratory_test_id'] ?? null)) {
                    $this->laboratory->request($c->patient, $test, $doctor, $c);
                }
            }

            if ($complete) {
                $this->complete($c, $doctor);
            } else {
                $this->audit->log('draft_saved', $c, 'Saved consultation draft');
            }

            return $c->fresh();
        });
    }

    private function complete(Consultation $c, User $doctor): void
    {
        $c->status = ConsultationStatus::Completed;
        $c->completed_at = now();
        $c->save();

        if ($rx = $c->prescriptions()->where('status', 'draft')->first()) {
            $this->prescriptions->finalize($rx, $doctor);
        }

        // Earlier pending follow-ups due around now are satisfied by this encounter.
        FollowUp::query()
            ->where('patient_id', $c->patient_id)
            ->where('status', FollowUpStatus::Pending->value)
            ->whereDate('follow_up_date', '<=', now()->addDays(3)->toDateString())
            ->update(['status' => FollowUpStatus::Completed->value, 'completed_visit_id' => $c->visit_id, 'updated_at' => now()]);

        if ($c->follow_up_date) {
            FollowUp::query()->create([
                'patient_id' => $c->patient_id,
                'consultation_id' => $c->id,
                'doctor_id' => $doctor->id,
                'follow_up_date' => $c->follow_up_date,
                'instructions' => $c->follow_up_instructions,
                'status' => FollowUpStatus::Pending,
                'created_by' => $doctor->id,
            ]);
        }

        $visit = $c->visit()->first();
        if (! $visit->consultations()->where('status', ConsultationStatus::Draft->value)->exists()) {
            $this->visits->complete($visit);
        }

        $this->audit->log('completed', $c, 'Completed consultation '.$c->consultation_number, [
            'snapshot' => $c->only(Consultation::CLINICAL_FIELDS),
        ]);
    }

    /** Tracked correction of a completed consultation; before/after values are preserved. */
    public function amend(Consultation $c, array $data, string $reason, User $doctor): Consultation
    {
        if ($c->isDraft()) {
            throw new BusinessRuleException('Drafts are edited directly; amendments apply to completed consultations.');
        }

        return DB::transaction(function () use ($c, $data, $reason, $doctor) {
            $changes = [];
            foreach (Consultation::CLINICAL_FIELDS as $field) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }
                $old = $c->getRawOriginal($field);
                $new = $data[$field] === '' ? null : $data[$field];
                if ((string) $old !== (string) $new) {
                    $changes[$field] = ['old' => $old, 'new' => $new];
                }
            }
            if (! $changes) {
                throw new BusinessRuleException('No changes were made.');
            }

            $c->fill(array_map(fn ($ch) => $ch['new'], $changes));
            $c->amendment_count++;
            $c->save();
            $c->amendments()->create(['reason' => $reason, 'changes' => $changes, 'amended_by' => $doctor->id]);

            if (isset($changes['follow_up_date']) && $c->follow_up_date) {
                FollowUp::query()->updateOrCreate(
                    ['consultation_id' => $c->id],
                    ['patient_id' => $c->patient_id, 'doctor_id' => $doctor->id, 'follow_up_date' => $c->follow_up_date, 'instructions' => $c->follow_up_instructions, 'status' => FollowUpStatus::Pending, 'created_by' => $doctor->id],
                );
            }

            $this->audit->log('amended', $c, 'Amended consultation '.$c->consultation_number, ['reason' => $reason, 'changes' => $changes]);

            return $c;
        });
    }

    /** Discard an empty/abandoned draft. Only possible while nothing was finalised from it. */
    public function discard(Consultation $c, User $doctor): void
    {
        if (! $c->isDraft() || $c->doctor_id !== $doctor->id) {
            throw new BusinessRuleException('Only your own draft consultations can be discarded.');
        }
        if ($c->laboratoryRequests()->exists()) {
            throw new BusinessRuleException('This draft already has laboratory requests and cannot be discarded.');
        }
        DB::transaction(function () use ($c) {
            foreach ($c->prescriptions()->get() as $rx) {
                $this->prescriptions->discardDraft($rx);
            }
            $c->vitals()->delete();
            $visit = $c->visit()->first();
            $this->audit->log('draft_discarded', $c, 'Discarded consultation draft '.$c->consultation_number);
            $c->delete();
            if (! $visit->consultations()->exists()) {
                $visit->update(['status' => VisitStatus::Waiting]);
            }
        });
    }
}
