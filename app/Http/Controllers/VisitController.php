<?php

namespace App\Http\Controllers;

use App\Enums\VisitStatus;
use App\Http\Requests\VisitRequest;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ConsultationService;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function __construct(private VisitService $visits) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Visit::class);
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(VisitStatus::class)],
            'doctor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $visits = Visit::query()->with(['patient:id,patient_code,full_name', 'doctor:id,name'])
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('visit_number', 'like', $t.'%')->orWhereHas('patient', fn ($p) => $p->search($t))))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('visit_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('visit_at', '<=', $d))
            ->latest('visit_at')->paginate(config('clinic.per_page'))->withQueryString();

        return view('visits.index', [
            'visits' => $visits, 'filters' => $f,
            'doctors' => User::query()->doctors()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Visit::class);
        $patient = $request->integer('patient') ? Patient::query()->find($request->integer('patient')) : null;

        return view('visits.create', [
            'patient' => $patient,
            'doctors' => User::query()->active()->doctors()->orderBy('name')->get(['id', 'name', 'specialization']),
            'defaultDoctor' => $request->user()->isDoctor() ? $request->user()->id : null,
        ]);
    }

    public function store(VisitRequest $request): RedirectResponse
    {
        $patient = Patient::query()->findOrFail($request->integer('patient_id'));
        $visit = $this->visits->create($patient, $request->validated(), $request->user());

        return redirect()->route('visits.show', $visit)->with('success', "Visit {$visit->visit_number} recorded.");
    }

    public function show(Request $request, Visit $visit): View
    {
        $this->authorize('view', $visit);
        $visit->load(['patient', 'doctor', 'appointment', 'creator:id,name']);
        $clinical = $request->user()->can('clinical.view');
        if ($clinical) {
            $visit->load(['consultations.doctor:id,name', 'consultations.vitals', 'consultations.prescription.items', 'consultations.laboratoryRequests', 'laboratoryReports']);
        }

        return view('visits.show', compact('visit', 'clinical'));
    }

    public function cancel(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorize('cancel', $visit);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->visits->cancel($visit, $request->user(), $data['reason']);

        return back()->with('success', 'Visit cancelled.');
    }

    public function consult(Request $request, Visit $visit, ConsultationService $consultations): RedirectResponse
    {
        $this->authorize('consult', $visit);
        $consultation = $consultations->start($visit, $request->user());

        return redirect()->route('consultations.edit', $consultation);
    }
}
