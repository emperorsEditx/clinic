<?php

namespace App\Reports;

use App\Models\MedicationRepetition;
use Illuminate\Support\Collection;

class RepeatPrescriptionReport extends Report
{
    public function key(): string
    {
        return 'repeat-prescriptions';
    }

    public function title(): string
    {
        return 'Repeat Prescription Report';
    }

    public function description(): string
    {
        return 'Every authorised medicine repetition with its source and reason.';
    }

    public function columns(): array
    {
        return ['date' => 'Date', 'patient' => 'Patient', 'medicine' => 'Medicine', 'source' => 'Source Rx', 'new' => 'New Rx', 'reason' => 'Reason', 'authorized_by' => 'Authorised by'];
    }

    public function rows(array $f): Collection
    {
        return MedicationRepetition::query()
            ->with(['patient:id,patient_code,full_name', 'sourcePrescription:id,prescription_number', 'newPrescription:id,prescription_number', 'newItem:id,medicine_name,strength', 'authorizer:id,name'])
            ->whereBetween('created_at', [$this->from($f), $this->to($f)])
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('authorized_by', $d))
            ->when($f['patient'] ?? null, fn ($q, $p) => $q->where('patient_id', $p))
            ->orderBy('created_at')->limit(self::MAX_ROWS)->get()
            ->map(fn (MedicationRepetition $r) => [
                'date' => fmt_date($r->created_at),
                'patient' => $r->patient->patient_code.' · '.$r->patient->full_name,
                'medicine' => trim($r->newItem->medicine_name.' '.$r->newItem->strength),
                'source' => $r->sourcePrescription->prescription_number,
                'new' => $r->newPrescription->prescription_number,
                'reason' => $r->reason,
                'authorized_by' => $r->authorizer->name,
            ]);
    }
}
