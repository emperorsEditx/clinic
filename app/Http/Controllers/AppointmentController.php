<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function __construct(private AppointmentService $appointments) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Appointment::class);
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'doctor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        $from = $f['from'] ?? (empty($f['q']) && empty($f['status']) ? today()->toDateString() : null);

        $appointments = Appointment::query()->with(['patient:id,patient_code,full_name,phone', 'doctor:id,name', 'visit:id,appointment_id'])
            ->when($f['q'] ?? null, fn ($q, $t) => $q->whereHas('patient', fn ($p) => $p->search($t)))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->when($from, fn ($q, $d) => $q->whereDate('appointment_date', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('appointment_date', '<=', $d))
            ->orderBy('appointment_date')->orderBy('appointment_time')
            ->paginate(config('clinic.per_page'))->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'filters' => $f + ['from' => $from],
            'doctors' => User::query()->doctors()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Appointment::class);
        $patient = $request->integer('patient') ? Patient::query()->find($request->integer('patient')) : null;

        return view('appointments.create', [
            'appointment' => new Appointment(['appointment_date' => today(), 'patient_id' => $patient?->id]),
            'patient' => $patient,
            'doctors' => User::query()->active()->doctors()->orderBy('name')->get(['id', 'name', 'specialization']),
            'slots' => $this->slots(),
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $appointment = $this->appointments->create($request->validated(), $request->user());

        return redirect()->route('appointments.show', $appointment)->with('success', "Appointment {$appointment->appointment_number} booked.");
    }

    public function show(Appointment $appointment): View
    {
        $this->authorize('view', $appointment);
        $appointment->load(['patient', 'doctor', 'visit']);

        return view('appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment): View
    {
        $this->authorize('update', $appointment);
        abort_unless($appointment->isOpen(), 403, 'This appointment can no longer be changed.');
        $appointment->load('patient');

        return view('appointments.edit', [
            'appointment' => $appointment,
            'patient' => $appointment->patient,
            'doctors' => User::query()->active()->doctors()->orderBy('name')->get(['id', 'name', 'specialization']),
            'slots' => $this->slots(),
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $this->appointments->update($appointment, $request->validated(), $request->user());

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment updated.');
    }

    public function status(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate([
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $this->appointments->changeStatus($appointment, AppointmentStatus::from($data['status']), $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'Appointment status updated.');
    }

    public function checkIn(Request $request, Appointment $appointment, VisitService $visits): RedirectResponse
    {
        $this->authorize('checkIn', $appointment);
        $data = $request->validate(['chief_complaint' => ['nullable', 'string', 'max:255']]);
        $visit = $visits->checkIn($appointment, $request->user(), $data['chief_complaint'] ?? null);

        return redirect()->route('visits.show', $visit)->with('success', "Checked in. Visit {$visit->visit_number} opened.");
    }

    /** @return list<string> */
    private function slots(): array
    {
        $step = max(5, (int) settings('appointment_slot_minutes', 15));
        $t = Carbon::createFromFormat('H:i', (string) settings('appointment_day_start', '09:00'));
        $end = Carbon::createFromFormat('H:i', (string) settings('appointment_day_end', '17:00'));
        $slots = [];
        while ($t < $end && count($slots) < 300) {
            $slots[] = $t->format('H:i');
            $t->addMinutes($step);
        }

        return $slots;
    }
}
