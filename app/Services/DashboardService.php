<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Enums\LabReportStatus;
use App\Enums\LabRequestStatus;
use App\Enums\VisitStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\FollowUp;
use App\Models\LaboratoryReport;
use App\Models\LaboratoryRequest;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Carbon;

class DashboardService
{
    /** @return array<string, mixed> */
    public function build(User $user, Carbon $from, Carbon $to): array
    {
        $fromD = $from->toDateString();
        $toD = $to->toDateString();
        $doctorScope = fn ($q) => $q->when($user->isDoctor() && ! $user->isAdmin(), fn ($w) => $w->where('doctor_id', $user->id));

        $cards = [];
        $sections = [];

        if ($user->can('patients.view')) {
            $cards['total_patients'] = ['label' => 'Total Patients', 'value' => Patient::query()->count(), 'icon' => 'users', 'url' => route('patients.index')];
            $cards['registered'] = ['label' => $from->isSameDay($to) && $from->isToday() ? 'Registered Today' : 'Registered in Period', 'value' => Patient::query()->whereBetween('registration_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count(), 'icon' => 'user-plus', 'url' => route('patients.index', ['from' => $fromD, 'to' => $toD])];
            $sections['recent_patients'] = Patient::query()->latest('id')->limit(6)->get(['id', 'patient_code', 'full_name', 'gender', 'registration_date', 'created_at']);
        }

        if ($user->can('appointments.view')) {
            $appointments = Appointment::query()->tap($doctorScope)->whereBetween('appointment_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
            $cards['appointments'] = ['label' => 'Appointments', 'value' => (clone $appointments)->count(), 'icon' => 'calendar', 'url' => route('appointments.index', ['from' => $fromD, 'to' => $toD])];
            $cards['pending_appointments'] = ['label' => 'Pending Appointments', 'value' => Appointment::query()->tap($doctorScope)->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::Confirmed->value])->whereDate('appointment_date', '>=', now()->toDateString())->count(), 'icon' => 'clock', 'url' => route('appointments.index', ['status' => 'scheduled'])];
            $sections['appointments'] = (clone $appointments)->with(['patient:id,patient_code,full_name', 'doctor:id,name'])
                ->orderBy('appointment_date')->orderBy('appointment_time')->limit(10)->get();
        }

        if ($user->can('visits.view')) {
            $cards['completed_visits'] = ['label' => 'Completed Visits', 'value' => Visit::query()->tap($doctorScope)->where('status', VisitStatus::Completed->value)->whereBetween('visit_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count(), 'icon' => 'check', 'url' => route('visits.index', ['from' => $fromD, 'to' => $toD, 'status' => 'completed'])];
            $sections['recent_visits'] = Visit::query()->tap($doctorScope)->with(['patient:id,patient_code,full_name', 'doctor:id,name'])->latest('visit_at')->limit(6)->get();
            $sections['waiting'] = Visit::query()->tap($doctorScope)->whereIn('status', [VisitStatus::Waiting->value, VisitStatus::InConsultation->value])->with(['patient:id,patient_code,full_name', 'doctor:id,name'])->oldest('visit_at')->limit(10)->get();
        }

        if ($user->can('lab.view')) {
            $pendingRequests = LaboratoryRequest::query()->whereIn('status', [LabRequestStatus::Requested->value, LabRequestStatus::SampleCollected->value, LabRequestStatus::Processing->value])->count();
            $awaitingReview = LaboratoryReport::query()->where('status', LabReportStatus::Completed->value)->count();
            $cards['pending_lab'] = ['label' => 'Pending Lab Reports', 'value' => $pendingRequests + $awaitingReview, 'hint' => "$pendingRequests in lab · $awaitingReview to review", 'icon' => 'beaker', 'url' => route('laboratory.requests.index', ['status' => 'pending'])];
            $sections['recent_reports'] = LaboratoryReport::query()->with('patient:id,patient_code,full_name')->latest('id')->limit(6)->get();
        }

        if ($user->can('clinical.view') || $user->can('appointments.view')) {
            $due = FollowUp::query()->tap($doctorScope)->where('status', FollowUpStatus::Pending->value)->whereDate('follow_up_date', '<=', $to->toDateString());
            $cards['follow_ups'] = ['label' => 'Follow-ups Due', 'value' => (clone $due)->count(), 'icon' => 'arrow-path', 'url' => route('follow-ups.index')];
            $sections['follow_ups'] = FollowUp::query()->tap($doctorScope)->where('status', FollowUpStatus::Pending->value)
                ->whereDate('follow_up_date', '>=', now()->subDays(7)->toDateString())
                ->with(['patient:id,patient_code,full_name,phone', 'doctor:id,name'])->orderBy('follow_up_date')->limit(8)->get();
        }

        if ($user->can('clinical.view')) {
            $sections['recent_consultations'] = Consultation::query()->tap($doctorScope)->with(['patient:id,patient_code,full_name', 'doctor:id,name'])->latest('id')->limit(6)->get();
        }

        return compact('cards', 'sections');
    }
}
