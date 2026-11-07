<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Merges every clinical event for a patient into one chronological stream,
 * filtered by what the viewing user is allowed to see.
 */
class TimelineService
{
    /** @return Collection<int, array{at: CarbonInterface, type: string, title: string, detail: ?string, url: ?string, color: string}> */
    public function for(Patient $patient, User $viewer): Collection
    {
        $events = collect([[
            'at' => $patient->created_at ?? $patient->registration_date,
            'type' => 'Registration',
            'title' => 'Registered as '.$patient->patient_code,
            'detail' => null,
            'url' => null,
            'color' => 'slate',
        ]]);

        if ($viewer->can('appointments.view')) {
            $patient->appointments()->with('doctor:id,name')->get()->each(function ($a) use ($events) {
                $events->push([
                    'at' => $a->scheduledAt(), 'type' => 'Appointment',
                    'title' => $a->appointment_type.' with '.$a->doctor->name,
                    'detail' => $a->status->label().($a->reason ? ' · '.$a->reason : ''),
                    'url' => route('appointments.show', $a), 'color' => 'blue',
                ]);
            });
        }

        if ($viewer->can('visits.view')) {
            $patient->visits()->with('doctor:id,name')->get()->each(function ($v) use ($events) {
                $events->push([
                    'at' => $v->visit_at, 'type' => 'Visit',
                    'title' => $v->visit_number.' · '.$v->visitTypeLabel().' with '.$v->doctor->name,
                    'detail' => $v->chief_complaint,
                    'url' => route('visits.show', $v), 'color' => 'indigo',
                ]);
            });
        }

        if ($viewer->can('clinical.view')) {
            $patient->consultations()->with('doctor:id,name')->get()->each(function ($c) use ($events) {
                $events->push([
                    'at' => $c->completed_at ?? $c->created_at, 'type' => $c->isDraft() ? 'Consultation (draft)' : 'Consultation',
                    'title' => $c->consultation_number.' by '.$c->doctor->name,
                    'detail' => $c->diagnosis ? 'Diagnosis: '.$c->diagnosis : null,
                    'url' => route('consultations.show', $c), 'color' => 'teal',
                ]);
            });
            $patient->prescriptions()->where('status', '!=', 'draft')->withCount('items')->get()->each(function ($p) use ($events) {
                $events->push([
                    'at' => $p->finalized_at ?? $p->created_at, 'type' => $p->repeat_of_id ? 'Repeat prescription' : ($p->previous_version_id ? 'Prescription amendment' : 'Prescription'),
                    'title' => $p->prescription_number.' · '.$p->items_count.' medicine(s)',
                    'detail' => $p->repeat_reason ?? $p->amendment_reason,
                    'url' => route('prescriptions.show', $p), 'color' => 'green',
                ]);
            });
            $patient->laboratoryRequests()->get()->each(function ($r) use ($events) {
                $events->push([
                    'at' => $r->created_at, 'type' => 'Lab request',
                    'title' => $r->test_name.' ('.$r->request_number.')',
                    'detail' => $r->status->label(),
                    'url' => route('laboratory.requests.show', $r), 'color' => 'amber',
                ]);
            });
            $patient->laboratoryReports()->get()->each(function ($r) use ($events) {
                $events->push([
                    'at' => $r->created_at, 'type' => 'Lab report',
                    'title' => $r->test_name.' report ('.$r->report_number.')',
                    'detail' => $r->status->label(),
                    'url' => route('laboratory.reports.show', $r), 'color' => 'orange',
                ]);
            });
        }

        if ($viewer->can('clinical.view') || $viewer->can('appointments.view')) {
            $patient->followUps()->get()->each(function ($f) use ($events) {
                $events->push([
                    'at' => $f->follow_up_date, 'type' => 'Follow-up',
                    'title' => 'Follow-up '.strtolower($f->status->label()),
                    'detail' => $f->instructions,
                    'url' => null, 'color' => 'rose',
                ]);
            });
        }

        return $events->filter(fn ($e) => $e['at'])->sortByDesc(fn ($e) => $e['at']->timestamp)->values();
    }
}
