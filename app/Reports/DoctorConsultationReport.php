<?php

namespace App\Reports;

use App\Models\Consultation;
use App\Models\LaboratoryRequest;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Collection;

class DoctorConsultationReport extends Report
{
    public function key(): string
    {
        return 'doctor-consultations';
    }

    public function title(): string
    {
        return 'Doctor-wise Consultation Report';
    }

    public function description(): string
    {
        return 'Completed consultations, prescriptions and lab requests per doctor.';
    }

    public function filters(): array
    {
        return ['doctor'];
    }

    public function columns(): array
    {
        return ['doctor' => 'Doctor', 'consultations' => 'Consultations', 'patients' => 'Unique patients', 'prescriptions' => 'Prescriptions', 'repeats' => 'Repeat Rx', 'lab_requests' => 'Lab requests', 'amendments' => 'Amended'];
    }

    public function rows(array $f): Collection
    {
        $range = [$this->from($f), $this->to($f)];
        $consults = Consultation::query()->where('status', 'completed')->whereBetween('completed_at', $range)
            ->selectRaw('doctor_id, COUNT(*) as c, COUNT(DISTINCT patient_id) as p, SUM(CASE WHEN amendment_count > 0 THEN 1 ELSE 0 END) as a')
            ->groupBy('doctor_id')->get()->keyBy('doctor_id');
        $rx = Prescription::query()->whereIn('status', ['finalized', 'superseded'])->whereBetween('finalized_at', $range)
            ->selectRaw('doctor_id, COUNT(*) as c, SUM(CASE WHEN repeat_of_id IS NOT NULL THEN 1 ELSE 0 END) as r')
            ->groupBy('doctor_id')->get()->keyBy('doctor_id');
        $labs = LaboratoryRequest::query()->whereBetween('created_at', $range)
            ->selectRaw('requested_by, COUNT(*) as c')->groupBy('requested_by')->pluck('c', 'requested_by');

        return User::withTrashed()->role(User::ROLE_DOCTOR)
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->whereKey($d))
            ->orderBy('name')->get()
            ->map(fn (User $u) => [
                'doctor' => $u->name,
                'consultations' => (int) ($consults[$u->id]->c ?? 0),
                'patients' => (int) ($consults[$u->id]->p ?? 0),
                'prescriptions' => (int) ($rx[$u->id]->c ?? 0),
                'repeats' => (int) ($rx[$u->id]->r ?? 0),
                'lab_requests' => (int) ($labs[$u->id] ?? 0),
                'amendments' => (int) ($consults[$u->id]->a ?? 0),
            ]);
    }

    public function totals(Collection $rows): ?array
    {
        return $this->sumColumns($rows, ['consultations', 'patients', 'prescriptions', 'repeats', 'lab_requests', 'amendments'], 'doctor');
    }
}
