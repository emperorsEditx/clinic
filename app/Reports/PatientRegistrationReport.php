<?php

namespace App\Reports;

use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Support\Collection;

class PatientRegistrationReport extends Report
{
    public function key(): string
    {
        return 'patient-registrations';
    }

    public function title(): string
    {
        return 'Patient Registration Report';
    }

    public function description(): string
    {
        return 'Patients registered within the selected period.';
    }

    public function filters(): array
    {
        return ['gender'];
    }

    public function columns(): array
    {
        return ['patient_code' => 'Patient ID', 'full_name' => 'Name', 'gender' => 'Gender', 'age' => 'Age', 'phone' => 'Phone', 'registration_date' => 'Registered', 'registered_by' => 'Registered by'];
    }

    public function rows(array $f): Collection
    {
        return Patient::query()->with('creator:id,name')
            ->whereBetween('registration_date', [$this->from($f)->toDateTimeString(), $this->to($f)->toDateTimeString()])
            ->when($f['gender'] ?? null, fn ($q, $g) => $q->where('gender', $g))
            ->orderBy('registration_date')->orderBy('id')->limit(self::MAX_ROWS)->get()
            ->map(fn (Patient $p) => [
                'patient_code' => $p->patient_code,
                'full_name' => $p->full_name,
                'gender' => $p->gender?->label(),
                'age' => $p->currentAge(),
                'phone' => $p->phone,
                'registration_date' => fmt_date($p->registration_date),
                'registered_by' => $p->creator?->name,
            ]);
    }

    public function statusOptions(): array
    {
        return Gender::options();
    }
}
