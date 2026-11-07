<?php

namespace App\Http\Controllers;

use App\Enums\ConsultationStatus;
use App\Enums\PrescriptionStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Requests\PrescriptionRequest;
use App\Http\Requests\RepeatPrescriptionRequest;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionTemplate;
use App\Models\User;
use App\Services\ConsultationService;
use App\Services\PdfService;
use App\Services\PrescriptionService;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PrescriptionController extends Controller
{
    public function __construct(private PrescriptionService $prescriptions) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Prescription::class);
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(PrescriptionStatus::class)],
            'doctor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'type' => ['nullable', Rule::in(['new', 'repeat', 'amendment'])],
        ]);

        $prescriptions = Prescription::query()->with(['patient:id,patient_code,full_name', 'doctor:id,name'])->withCount('items')
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('prescription_number', 'like', $t.'%')->orWhereHas('patient', fn ($p) => $p->search($t))->orWhereHas('items', fn ($i) => $i->where('medicine_name', 'like', '%'.$t.'%'))))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s), fn ($q) => $q->where('status', '!=', 'draft'))
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('prescription_date', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('prescription_date', '<=', $d))
            ->when(($f['type'] ?? null) === 'repeat', fn ($q) => $q->whereNotNull('repeat_of_id'))
            ->when(($f['type'] ?? null) === 'amendment', fn ($q) => $q->whereNotNull('previous_version_id'))
            ->when(($f['type'] ?? null) === 'new', fn ($q) => $q->whereNull('repeat_of_id')->whereNull('previous_version_id'))
            ->latest('id')->paginate(config('clinic.per_page'))->withQueryString();

        return view('prescriptions.index', [
            'prescriptions' => $prescriptions, 'filters' => $f,
            'doctors' => User::query()->doctors()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Prescription $prescription): View
    {
        $this->authorize('view', $prescription);
        $prescription->load(['patient', 'doctor', 'items', 'courses', 'consultation', 'previousVersion', 'nextVersion', 'repeatOf', 'repeats', 'authorizer']);

        return view('prescriptions.show', ['rx' => $prescription]);
    }

    /** Draft editor for amendments and repeats (consultation drafts are edited in the consultation workspace). */
    public function edit(Prescription $prescription): View|RedirectResponse
    {
        $this->authorize('update', $prescription);
        if ($prescription->consultation()->first()->status === ConsultationStatus::Draft && ! $prescription->previous_version_id && ! $prescription->repeat_of_id) {
            return redirect()->route('consultations.edit', $prescription->consultation_id);
        }
        $prescription->load(['patient.allergies' => fn ($q) => $q->where('status', 'active'), 'items', 'previousVersion', 'repeatOf']);

        return view('prescriptions.edit', [
            'rx' => $prescription,
            'templates' => PrescriptionTemplate::query()->visibleTo(auth()->user())->orderBy('name')->get(),
        ]);
    }

    public function update(PrescriptionRequest $request, Prescription $prescription): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($prescription, $data, $request) {
            $this->prescriptions->syncItems($prescription, $data['items'], $data);
            if ($request->boolean('finalize')) {
                $this->prescriptions->finalize($prescription->fresh(), $request->user());
            }
        });

        return $request->boolean('finalize')
            ? redirect()->route('prescriptions.show', $prescription)->with('success', 'Prescription finalised.')
            : back()->with('success', 'Draft saved.');
    }

    public function amend(Request $request, Prescription $prescription): RedirectResponse
    {
        $this->authorize('amend', $prescription);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);
        $new = $this->prescriptions->amend($prescription, $request->user(), $data['reason']);

        return redirect()->route('prescriptions.edit', $new)->with('success', 'Amendment draft created. The original remains unchanged until you finalise.');
    }

    public function destroy(Prescription $prescription): RedirectResponse
    {
        $this->authorize('delete', $prescription);
        $patient = $prescription->patient_id;
        $this->prescriptions->discardDraft($prescription);

        return redirect()->route('patients.show', [$patient, 'tab' => 'prescriptions'])->with('success', 'Draft discarded.');
    }

    public function pdf(Request $request, Prescription $prescription, PdfService $pdf): Response
    {
        $this->authorize('view', $prescription);

        return $pdf->prescription($prescription, $request->boolean('download'));
    }

    public function repeatForm(Request $request, Patient $patient): View
    {
        $this->authorize('repeat', [Prescription::class, $patient]);
        $prescriptions = $patient->prescriptions()->with(['items.course', 'doctor:id,name'])
            ->whereIn('status', ['finalized', 'superseded'])->latest('id')->get();

        return view('prescriptions.repeat', [
            'patient' => $patient,
            'prescriptions' => $prescriptions,
            'selected' => $prescriptions->firstWhere('id', $request->integer('from')) ?? $prescriptions->firstWhere('status', PrescriptionStatus::Finalized),
            'drafts' => $patient->consultations()->where('status', 'draft')->where('doctor_id', $request->user()->id)->latest('id')->get(),
        ]);
    }

    public function repeat(RepeatPrescriptionRequest $request, Patient $patient, VisitService $visits, ConsultationService $consultations): RedirectResponse
    {
        $data = $request->validated();
        $doctor = $request->user();
        $source = Prescription::query()->findOrFail($data['source_prescription_id']);

        $rx = DB::transaction(function () use ($data, $doctor, $patient, $source, $visits, $consultations) {
            if (! empty($data['consultation_id'])) {
                $consultation = Consultation::query()->findOrFail($data['consultation_id']);
                if ($consultation->doctor_id !== $doctor->id) {
                    throw new BusinessRuleException('You can only attach a repeat to your own draft consultation.');
                }
            } else {
                // Medication-review encounter documenting the authorisation.
                $visit = $visits->create($patient, ['doctor_id' => $doctor->id, 'visit_type' => 'medication_review', 'chief_complaint' => 'Repeat prescription: '.$data['reason']], $doctor);
                $consultation = $consultations->start($visit, $doctor);
                $consultations->save($consultation, [
                    'symptoms' => 'Medication review',
                    'diagnosis' => 'Repeat medication authorised',
                    'treatment_plan' => 'Repeat of '.$source->prescription_number.': '.$data['reason'],
                ], $doctor, true);
            }

            $items = [];
            foreach ($data['selected'] as $itemId) {
                $items[$itemId] = $data['items'][$itemId] ?? [];
            }

            return $this->prescriptions->repeat($source, $consultation->fresh(), $items, $data['reason'], $doctor, finalize: true);
        });

        return redirect()->route('prescriptions.show', $rx)->with('success', "Repeat prescription {$rx->prescription_number} authorised.");
    }

    public function saveTemplate(Request $request, Prescription $prescription): RedirectResponse
    {
        $this->authorize('view', $prescription);
        abort_unless($request->user()->can('prescriptions.manage'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'is_shared' => ['nullable', 'boolean']]);
        PrescriptionTemplate::query()->create([
            'name' => $data['name'],
            'doctor_id' => $request->user()->id,
            'is_shared' => $request->boolean('is_shared'),
            'general_instructions' => $prescription->general_instructions,
            'items' => $prescription->items()->get()->map->toTemplateArray()->all(),
        ]);

        return back()->with('success', 'Saved as template "'.$data['name'].'".');
    }
}
