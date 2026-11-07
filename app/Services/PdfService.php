<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Prescription;
use App\Reports\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class PdfService
{
    public function __construct(private AuditLogger $audit) {}

    public function prescription(Prescription $rx, bool $download = false): Response
    {
        $rx->load(['patient', 'doctor', 'items', 'consultation:id,diagnosis', 'repeatOf:id,prescription_number', 'previousVersion:id,prescription_number']);
        $this->audit->log('printed', $rx, 'Generated prescription PDF '.$rx->prescription_number);

        $pdf = Pdf::loadView('pdf.prescription', ['rx' => $rx, 'branding' => $this->branding()])->setPaper('a4');
        $name = $rx->prescription_number.'.pdf';

        return $download ? $pdf->download($name) : $pdf->stream($name);
    }

    public function patientHistory(Patient $patient): Response
    {
        $patient->load([
            'conditions', 'allergies', 'otherMedications', 'medicalHistories.recorder:id,name',
            'visits' => fn ($q) => $q->with('doctor:id,name')->latest('visit_at'),
            'consultations' => fn ($q) => $q->with(['doctor:id,name', 'vitals'])->where('status', 'completed')->latest('completed_at'),
            'prescriptions' => fn ($q) => $q->with(['items', 'doctor:id,name'])->whereIn('status', ['finalized', 'superseded'])->latest('id'),
            'medicationCourses' => fn ($q) => $q->latest('start_date'),
            'laboratoryReports' => fn ($q) => $q->latest('report_date'),
        ]);
        $this->audit->log('printed', $patient, 'Generated patient history PDF');

        return Pdf::loadView('pdf.patient-history', ['patient' => $patient, 'branding' => $this->branding()])
            ->setPaper('a4')->stream($patient->patient_code.'-history.pdf');
    }

    public function report(Report $report, Collection $rows, array $filters, string $name): Response
    {
        return Pdf::loadView('pdf.report', ['report' => $report, 'rows' => $rows, 'filters' => $filters, 'totals' => $report->totals($rows), 'branding' => $this->branding()])
            ->setPaper('a4', count($report->columns()) > 5 ? 'landscape' : 'portrait')
            ->download($name);
    }

    private function branding(): array
    {
        return settings()->all() + ['logo_data_uri' => settings()->logoDataUri()];
    }
}
