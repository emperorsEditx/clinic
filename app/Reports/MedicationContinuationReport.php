<?php

namespace App\Reports;

use App\Enums\MedicationCourseStatus;
use App\Models\MedicationCourse;
use Illuminate\Support\Collection;

class MedicationContinuationReport extends Report
{
    public function key(): string
    {
        return 'medication-continuation';
    }

    public function title(): string
    {
        return 'Medication Continuation Report';
    }

    public function description(): string
    {
        return 'Medication courses overlapping the period, with expected end dates.';
    }

    public function filters(): array
    {
        return ['patient', 'status'];
    }

    public function statusOptions(): array
    {
        return MedicationCourseStatus::options();
    }

    public function columns(): array
    {
        return ['patient' => 'Patient', 'medicine' => 'Medicine', 'prescription' => 'Prescription', 'start' => 'Start', 'days' => 'Days', 'end' => 'Expected end', 'status' => 'Status', 'instructions' => 'Instructions'];
    }

    public function rows(array $f): Collection
    {
        return MedicationCourse::query()->with(['patient:id,patient_code,full_name', 'prescription:id,prescription_number'])
            ->whereDate('start_date', '<=', $this->to($f)->toDateString())
            ->whereDate('expected_end_date', '>=', $this->from($f)->toDateString())
            ->when($f['patient'] ?? null, fn ($q, $p) => $q->where('patient_id', $p))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('expected_end_date')->limit(self::MAX_ROWS)->get()
            ->map(fn (MedicationCourse $c) => [
                'patient' => $c->patient->patient_code.' · '.$c->patient->full_name,
                'medicine' => $c->medicine_name,
                'prescription' => $c->prescription->prescription_number,
                'start' => fmt_date($c->start_date),
                'days' => $c->duration_days,
                'end' => fmt_date($c->expected_end_date),
                'status' => $c->status->label(),
                'instructions' => $c->continuation_instructions,
            ]);
    }
}
