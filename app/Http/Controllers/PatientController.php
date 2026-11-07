<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Enums\MedicationCourseStatus;
use App\Exceptions\DuplicatePatientException;
use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;
use App\Services\PdfService;
use App\Services\TimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PatientController extends Controller
{
    public function __construct(private PatientService $patients) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Patient::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'doctor' => ['nullable', 'integer'],
            'visit_from' => ['nullable', 'date'],
            'visit_to' => ['nullable', 'date'],
            'archived' => ['nullable', 'boolean'],
        ]);

        $patients = Patient::query()
            ->when($request->boolean('archived') && $request->user()->can('patients.delete'), fn ($q) => $q->onlyTrashed())
            ->search($filters['q'] ?? null)
            ->when($filters['gender'] ?? null, fn ($q, $g) => $q->where('gender', $g))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('registration_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('registration_date', '<=', $d))
            ->when(($filters['doctor'] ?? null) || ($filters['visit_from'] ?? null) || ($filters['visit_to'] ?? null), function ($q) use ($filters) {
                $q->whereHas('visits', function ($v) use ($filters) {
                    $v->when($filters['doctor'] ?? null, fn ($w, $d) => $w->where('doctor_id', $d))
                        ->when($filters['visit_from'] ?? null, fn ($w, $d) => $w->whereDate('visit_at', '>=', $d))
                        ->when($filters['visit_to'] ?? null, fn ($w, $d) => $w->whereDate('visit_at', '<=', $d));
                });
            })
            ->withMax('visits', 'visit_at')
            ->latest('id')
            ->paginate(config('clinic.per_page'))->withQueryString();

        return view('patients.index', [
            'patients' => $patients,
            'filters' => $filters,
            'doctors' => User::query()->doctors()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** JSON search used by the global search box and patient pickers. */
    public function search(Request $request): JsonResponse
    {
        // Lab technicians may look up a patient to attach an external report, but get no profile link.
        $canView = $request->user()->can('viewAny', Patient::class);
        abort_unless($canView || $request->user()->can('lab.upload'), 403);
        $q = (string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '');

        $results = strlen(trim($q)) < 2 ? collect() : Patient::query()->search($q)->orderBy('full_name')->limit(10)
            ->get(['id', 'patient_code', 'full_name', 'phone', 'gender', 'age', 'date_of_birth', 'age_recorded_on', 'registration_date']);

        return response()->json($results->map(fn (Patient $p) => [
            'id' => $p->id,
            'code' => $p->patient_code,
            'name' => $p->full_name,
            'meta' => collect([$p->gender?->label(), $p->currentAge() !== null ? $p->currentAge().' y' : null, $p->phone])->filter()->implode(' · '),
            'url' => $canView ? route('patients.show', $p) : null,
        ]));
    }

    public function create(): View
    {
        $this->authorize('create', Patient::class);

        return view('patients.create', ['patient' => new Patient(['registration_date' => today()])]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        try {
            $patient = $this->patients->register($request->patientData(), $request->user(), $request->boolean('confirm_duplicate'));
        } catch (DuplicatePatientException $e) {
            return back()->withInput()->with('duplicates', $e->matches->map(fn ($p) => [
                'code' => $p->patient_code, 'name' => $p->full_name, 'phone' => $p->phone, 'url' => route('patients.show', $p),
            ])->all());
        }

        return redirect()->route('patients.show', $patient)->with('success', "Patient registered as {$patient->patient_code}.");
    }

    public function show(Request $request, Patient $patient, TimelineService $timeline): View
    {
        $this->authorize('view', $patient);
        $user = $request->user();
        $tab = $request->query('tab', 'overview');
        $clinical = $user->can('viewClinical', $patient);

        $data = ['patient' => $patient, 'tab' => $tab, 'clinical' => $clinical];

        if ($clinical) {
            $patient->load([
                'conditions' => fn ($q) => $q->with('recorder:id,name')->latest('id'),
                'allergies' => fn ($q) => $q->with('recorder:id,name')->latest('id'),
                'otherMedications' => fn ($q) => $q->with('recorder:id,name')->latest('id'),
                'medicalHistories' => fn ($q) => $q->with('recorder:id,name')->latest('id'),
                'medicationCourses' => fn ($q) => $q->with('prescription:id,prescription_number')->latest('start_date'),
            ]);
        }

        $data['activeCourses'] = $clinical ? $patient->medicationCourses->where('status', MedicationCourseStatus::Active) : collect();

        $data += match ($tab) {
            'visits' => ['visits' => $patient->visits()->with(['doctor:id,name', 'consultations:id,visit_id,diagnosis,status'])->latest('visit_at')->paginate(15, ['*'], 'page')->withQueryString()],
            'consultations' => $clinical ? ['consultations' => $patient->consultations()->with(['doctor:id,name', 'vitals'])->latest('id')->paginate(15)->withQueryString()] : [],
            'prescriptions' => $clinical ? ['prescriptions' => $patient->prescriptions()->with(['doctor:id,name', 'items'])->latest('id')->paginate(15)->withQueryString()] : [],
            'laboratory' => $clinical ? [
                'labRequests' => $patient->laboratoryRequests()->with('requester:id,name')->latest('id')->get(),
                'labReports' => $patient->laboratoryReports()->with(['uploader:id,name', 'reviewer:id,name'])->latest('report_date')->get(),
            ] : [],
            'medications' => $clinical ? ['repetitions' => $patient->medicationRepetitions()->with(['newItem:id,medicine_name,strength', 'sourcePrescription:id,prescription_number', 'newPrescription:id,prescription_number', 'authorizer:id,name'])->latest('id')->get()] : [],
            'timeline' => ['timeline' => $this->paginateCollection($timeline->for($patient, $user), 25, $request)],
            default => [
                'recentVisits' => $patient->visits()->with('doctor:id,name')->latest('visit_at')->limit(5)->get(),
                'upcomingAppointments' => $patient->appointments()->with('doctor:id,name')->whereDate('appointment_date', '>=', today())->whereIn('status', ['scheduled', 'confirmed'])->orderBy('appointment_date')->limit(5)->get(),
                'lastConsultation' => $clinical ? $patient->consultations()->with(['doctor:id,name', 'vitals'])->where('status', 'completed')->latest('completed_at')->first() : null,
                'counts' => [
                    'visits' => $patient->visits()->count(),
                    'consultations' => $clinical ? $patient->consultations()->count() : null,
                    'prescriptions' => $clinical ? $patient->prescriptions()->whereIn('status', ['finalized', 'superseded'])->count() : null,
                    'lab' => $clinical ? $patient->laboratoryReports()->count() : null,
                ],
            ],
        };

        if (! $clinical && in_array($tab, ['history', 'consultations', 'prescriptions', 'medications', 'laboratory'], true)) {
            abort(403);
        }

        return view('patients.show', $data);
    }

    public function edit(Patient $patient): View
    {
        $this->authorize('update', $patient);

        return view('patients.edit', compact('patient'));
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $this->patients->update($patient, $request->patientData(), $request->user());

        return redirect()->route('patients.show', $patient)->with('success', 'Patient details updated.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('delete', $patient);
        $this->patients->archive($patient);

        return redirect()->route('patients.index')->with('success', "Patient {$patient->patient_code} archived. Their records are retained.");
    }

    public function restore(int $patient): RedirectResponse
    {
        $model = Patient::onlyTrashed()->findOrFail($patient);
        $this->authorize('restore', $model);
        $this->patients->restore($model);

        return redirect()->route('patients.show', $model)->with('success', 'Patient restored.');
    }

    /** Print-friendly profile (browser print). */
    public function printProfile(Request $request, Patient $patient): View
    {
        $this->authorize('view', $patient);
        $clinical = $request->user()->can('viewClinical', $patient);
        if ($clinical) {
            $patient->load(['conditions', 'allergies', 'otherMedications', 'medicationCourses' => fn ($q) => $q->where('status', 'active')]);
        }

        return view('patients.print', ['patient' => $patient, 'clinical' => $clinical,
            'visits' => $patient->visits()->with('doctor:id,name')->latest('visit_at')->limit(15)->get()]);
    }

    /** Complete history PDF (clinical). */
    public function historyPdf(Patient $patient, PdfService $pdf): Response
    {
        $this->authorize('viewClinical', $patient);

        return $pdf->patientHistory($patient);
    }

    private function paginateCollection($items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));

        return new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);
    }
}
