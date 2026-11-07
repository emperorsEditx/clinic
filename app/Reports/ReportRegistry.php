<?php

namespace App\Reports;

class ReportRegistry
{
    /** @return array<string, Report> */
    public function all(): array
    {
        $reports = [
            new PatientRegistrationReport,
            new VisitsReport('day'),
            new VisitsReport('month'),
            new DoctorConsultationReport,
            new AppointmentReport,
            new PrescriptionHistoryReport,
            new MedicationContinuationReport,
            new RepeatPrescriptionReport,
            new LaboratorySummaryReport,
            new FollowUpReport,
        ];

        return collect($reports)->keyBy(fn (Report $r) => $r->key())->all();
    }

    public function find(string $key): ?Report
    {
        return $this->all()[$key] ?? null;
    }
}
