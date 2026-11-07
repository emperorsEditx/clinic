<?php

namespace App\Reports;

use App\Enums\PrescriptionStatus;
use App\Models\Prescription;
use Illuminate\Support\Collection;

class PrescriptionHistoryReport extends Report
{
    public function key(): string
    {
        return 'prescription-history';
    }

    public function title(): string
    {
        return 'Prescription History';
    }

    public function description(): string
    {
        return 'Finalised prescriptions with their medicines.';
    }

    public function filters(): array
    {
        return ['doctor', 'patient', 'status'];
    }

    public function statusOptions(): array
    {
        return array_intersect_key(PrescriptionStatus::options(), array_flip(['finalized', 'superseded']));
    }

    public function columns(): array
    {
        return ['number' => 'Prescription', 'date' => 'Date', 'patient' => 'Patient', 'doctor' => 'Doctor', 'type' => 'Type', 'medicines' => 'Medicines', 'status' => 'Status'];
    }

    public function rows(array $f): Collection
    {
        return Prescription::query()->with(['patient:id,patient_code,full_name', 'doctor:id,name', 'items:id,prescription_id,medicine_name,strength,duration_days'])
            ->whereIn('status', ['finalized', 'superseded'])
            ->whereBetween('prescription_date', [$this->from($f)->toDateTimeString(), $this->to($f)->toDateTimeString()])
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->when($f['patient'] ?? null, fn ($q, $p) => $q->where('patient_id', $p))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('prescription_date')->orderBy('id')->limit(self::MAX_ROWS)->get()
            ->map(fn (Prescription $p) => [
                'number' => $p->prescription_number,
                'date' => fmt_date($p->prescription_date),
                'patient' => $p->patient->patient_code.' · '.$p->patient->full_name,
                'doctor' => $p->doctor->name,
                'type' => $p->repeat_of_id ? 'Repeat' : ($p->previous_version_id ? 'Amendment v'.$p->version : 'New'),
                'medicines' => $p->items->map(fn ($i) => trim($i->medicine_name.' '.$i->strength).' ×'.$i->duration_days.'d')->implode('; '),
                'status' => $p->status->label(),
            ]);
    }
}
