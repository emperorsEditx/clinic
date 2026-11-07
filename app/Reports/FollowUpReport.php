<?php

namespace App\Reports;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use Illuminate\Support\Collection;

class FollowUpReport extends Report
{
    public function key(): string
    {
        return 'follow-ups';
    }

    public function title(): string
    {
        return 'Follow-up Report';
    }

    public function description(): string
    {
        return 'Scheduled follow-ups in the period and whether they happened.';
    }

    public function filters(): array
    {
        return ['doctor', 'patient', 'status'];
    }

    public function statusOptions(): array
    {
        return FollowUpStatus::options();
    }

    public function columns(): array
    {
        return ['date' => 'Follow-up date', 'patient' => 'Patient', 'phone' => 'Phone', 'doctor' => 'Doctor', 'status' => 'Status', 'instructions' => 'Instructions'];
    }

    public function rows(array $f): Collection
    {
        return FollowUp::query()->with(['patient:id,patient_code,full_name,phone', 'doctor:id,name'])
            ->whereBetween('follow_up_date', [$this->from($f)->toDateTimeString(), $this->to($f)->toDateTimeString()])
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->when($f['patient'] ?? null, fn ($q, $p) => $q->where('patient_id', $p))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('follow_up_date')->limit(self::MAX_ROWS)->get()
            ->map(fn (FollowUp $x) => [
                'date' => fmt_date($x->follow_up_date),
                'patient' => $x->patient->patient_code.' · '.$x->patient->full_name,
                'phone' => $x->patient->phone,
                'doctor' => $x->doctor?->name,
                'status' => $x->status->label(),
                'instructions' => $x->instructions,
            ]);
    }
}
