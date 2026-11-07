<?php

namespace App\Reports;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Collection;

class AppointmentReport extends Report
{
    public function key(): string
    {
        return 'appointments';
    }

    public function title(): string
    {
        return 'Appointment Report';
    }

    public function description(): string
    {
        return 'Appointments in the period with their outcome.';
    }

    public function filters(): array
    {
        return ['doctor', 'patient', 'status'];
    }

    public function statusOptions(): array
    {
        return AppointmentStatus::options();
    }

    public function columns(): array
    {
        return ['number' => 'Appointment', 'date' => 'Date', 'time' => 'Time', 'patient' => 'Patient', 'doctor' => 'Doctor', 'type' => 'Type', 'status' => 'Status', 'reason' => 'Reason'];
    }

    public function rows(array $f): Collection
    {
        return Appointment::query()->with(['patient:id,patient_code,full_name', 'doctor:id,name'])
            ->whereBetween('appointment_date', [$this->from($f)->toDateTimeString(), $this->to($f)->toDateTimeString()])
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->when($f['patient'] ?? null, fn ($q, $p) => $q->where('patient_id', $p))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('appointment_date')->orderBy('appointment_time')->limit(self::MAX_ROWS)->get()
            ->map(fn (Appointment $a) => [
                'number' => $a->appointment_number,
                'date' => fmt_date($a->appointment_date),
                'time' => fmt_time($a->scheduledAt()),
                'patient' => $a->patient->patient_code.' · '.$a->patient->full_name,
                'doctor' => $a->doctor->name,
                'type' => $a->appointment_type,
                'status' => $a->status->label(),
                'reason' => $a->reason,
            ]);
    }
}
