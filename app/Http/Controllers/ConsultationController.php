<?php

namespace App\Http\Controllers;

use App\Enums\ConsultationStatus;
use App\Http\Requests\AmendConsultationRequest;
use App\Http\Requests\ConsultationRequest;
use App\Models\Consultation;
use App\Models\LaboratoryTest;
use App\Models\PrescriptionTemplate;
use App\Models\User;
use App\Services\ConsultationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function __construct(private ConsultationService $consultations) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Consultation::class);
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ConsultationStatus::class)],
            'doctor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'mine' => ['nullable', 'boolean'],
        ]);

        $consultations = Consultation::query()->with(['patient:id,patient_code,full_name', 'doctor:id,name'])
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('consultation_number', 'like', $t.'%')->orWhere('diagnosis', 'like', '%'.$t.'%')->orWhereHas('patient', fn ($p) => $p->search($t))))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->when($request->boolean('mine'), fn ($q) => $q->where('doctor_id', $request->user()->id))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')->paginate(config('clinic.per_page'))->withQueryString();

        return view('consultations.index', [
            'consultations' => $consultations, 'filters' => $f,
            'doctors' => User::query()->doctors()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Consultation $consultation): View
    {
        $this->authorize('view', $consultation);
        $consultation->load(['patient', 'doctor', 'visit', 'vitals', 'prescriptions.items', 'laboratoryRequests.reports', 'amendments.author:id,name', 'followUp']);

        return view('consultations.show', ['c' => $consultation]);
    }

    /** The doctor's consultation workspace (draft). */
    public function edit(Consultation $consultation): View|RedirectResponse
    {
        $this->authorize('view', $consultation);
        if (! $consultation->isDraft()) {
            return redirect()->route('consultations.show', $consultation);
        }
        $this->authorize('update', $consultation);

        $consultation->load(['vitals', 'visit', 'laboratoryRequests', 'prescriptions' => fn ($q) => $q->where('status', 'draft')->with('items')]);
        $patient = $consultation->patient()->with([
            'allergies' => fn ($q) => $q->where('status', 'active'),
            'conditions' => fn ($q) => $q->latest('id'),
            'otherMedications' => fn ($q) => $q->where('status', 'current'),
            'medicationCourses' => fn ($q) => $q->where('status', 'active')->latest('start_date'),
        ])->first();

        return view('consultations.edit', [
            'c' => $consultation,
            'patient' => $patient,
            'draftRx' => $consultation->prescriptions->first(),
            'previousConsultations' => $patient->consultations()->with(['doctor:id,name', 'vitals'])->where('status', 'completed')->latest('completed_at')->limit(5)->get(),
            'previousPrescriptions' => $patient->prescriptions()->with('items')->where('status', 'finalized')->latest('id')->limit(5)->get(),
            'previousReports' => $patient->laboratoryReports()->latest('report_date')->limit(6)->get(),
            'labTests' => LaboratoryTest::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get(['id', 'name', 'category']),
            'templates' => PrescriptionTemplate::query()->visibleTo(auth()->user())->orderBy('name')->get(),
        ]);
    }

    public function update(ConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $complete = $request->input('action') === 'complete';
        $this->consultations->save($consultation, $request->validated(), $request->user(), $complete);

        return $complete
            ? redirect()->route('consultations.show', $consultation)->with('success', 'Consultation completed.'.($consultation->prescriptions()->where('status', 'finalized')->exists() ? ' Prescription finalised.' : ''))
            : redirect()->route('consultations.edit', $consultation)->with('success', 'Draft saved.');
    }

    public function amendForm(Consultation $consultation): View
    {
        $this->authorize('amend', $consultation);
        $consultation->load('patient');

        return view('consultations.amend', ['c' => $consultation]);
    }

    public function amend(AmendConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();
        $this->consultations->amend($consultation, collect($data)->except('reason')->all(), $data['reason'], $request->user());

        return redirect()->route('consultations.show', $consultation)->with('success', 'Amendment recorded. Original values are preserved in the amendment history.');
    }

    public function destroy(Request $request, Consultation $consultation): RedirectResponse
    {
        $this->authorize('delete', $consultation);
        $visit = $consultation->visit_id;
        $this->consultations->discard($consultation, $request->user());

        return redirect()->route('visits.show', $visit)->with('success', 'Draft consultation discarded.');
    }
}
