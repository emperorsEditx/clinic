<?php

namespace App\Http\Controllers\Laboratory;

use App\Enums\LabRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LabRequestRequest;
use App\Models\LaboratoryRequest;
use App\Models\LaboratoryTest;
use App\Models\Patient;
use App\Services\LaboratoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LabRequestController extends Controller
{
    public function __construct(private LaboratoryService $lab) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LaboratoryRequest::class);
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([...LabRequestStatus::values(), 'pending'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $requests = LaboratoryRequest::query()->with(['patient:id,patient_code,full_name', 'requester:id,name'])->withCount('reports')
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('test_name', 'like', '%'.$t.'%')->orWhere('request_number', 'like', $t.'%')->orWhereHas('patient', fn ($p) => $p->search($t))))
            ->when(($f['status'] ?? null) === 'pending', fn ($q) => $q->whereIn('status', ['requested', 'sample_collected', 'processing']))
            ->when(($f['status'] ?? null) && $f['status'] !== 'pending', fn ($q) => $q->where('status', $f['status']))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByRaw("CASE priority WHEN 'stat' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END")
            ->latest('id')->paginate(config('clinic.per_page'))->withQueryString();

        return view('laboratory.requests.index', ['requests' => $requests, 'filters' => $f]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', LaboratoryRequest::class);

        return view('laboratory.requests.create', [
            'patient' => $request->integer('patient') ? Patient::query()->find($request->integer('patient')) : null,
            'tests' => LaboratoryTest::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function store(LabRequestRequest $request): RedirectResponse
    {
        $patient = Patient::query()->findOrFail($request->integer('patient_id'));
        $req = $this->lab->request($patient, $request->validated(), $request->user());

        return redirect()->route('laboratory.requests.show', $req)->with('success', "Lab request {$req->request_number} created.");
    }

    public function show(LaboratoryRequest $laboratoryRequest): View
    {
        $this->authorize('view', $laboratoryRequest);
        $laboratoryRequest->load(['patient', 'requester', 'consultation', 'visit', 'reports.uploader:id,name', 'reports.reviewer:id,name']);

        return view('laboratory.requests.show', ['req' => $laboratoryRequest]);
    }

    public function status(Request $request, LaboratoryRequest $laboratoryRequest): RedirectResponse
    {
        $this->authorize('updateStatus', $laboratoryRequest);
        $data = $request->validate([
            'status' => ['required', Rule::enum(LabRequestStatus::class)],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $this->lab->changeStatus($laboratoryRequest, LabRequestStatus::from($data['status']), $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'Request status updated.');
    }
}
