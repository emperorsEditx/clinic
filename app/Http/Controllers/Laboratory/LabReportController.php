<?php

namespace App\Http\Controllers\Laboratory;

use App\Enums\LabReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LabReportUploadRequest;
use App\Models\LaboratoryReport;
use App\Models\LaboratoryRequest;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\LaboratoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LabReportController extends Controller
{
    public function __construct(private LaboratoryService $lab) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LaboratoryReport::class);
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(LabReportStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $reports = LaboratoryReport::query()->with(['patient:id,patient_code,full_name', 'uploader:id,name', 'reviewer:id,name'])
            ->search($f['q'] ?? null)
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('report_date', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('report_date', '<=', $d))
            ->latest('report_date')->latest('id')->paginate(config('clinic.per_page'))->withQueryString();

        return view('laboratory.reports.index', ['reports' => $reports, 'filters' => $f]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', LaboratoryReport::class);
        $req = $request->integer('request') ? LaboratoryRequest::query()->with('patient')->find($request->integer('request')) : null;
        $patient = $req?->patient ?? ($request->integer('patient') ? Patient::query()->find($request->integer('patient')) : null);

        return view('laboratory.reports.create', ['req' => $req, 'patient' => $patient]);
    }

    public function store(LabReportUploadRequest $request): RedirectResponse
    {
        $patient = Patient::query()->findOrFail($request->integer('patient_id'));
        $report = $this->lab->upload($patient, $request->file('file'), $request->validated(), $request->user());

        return redirect()->route('laboratory.reports.show', $report)->with('success', "Report {$report->report_number} uploaded.");
    }

    public function storeForRequest(LabReportUploadRequest $request, LaboratoryRequest $laboratoryRequest): RedirectResponse
    {
        $this->authorize('upload', $laboratoryRequest);
        $report = $this->lab->upload($laboratoryRequest->patient()->firstOrFail(), $request->file('file'), $request->validated(), $request->user(), $laboratoryRequest);

        return redirect()->route('laboratory.requests.show', $laboratoryRequest)->with('success', "Report {$report->report_number} uploaded.");
    }

    public function show(LaboratoryReport $report): View
    {
        $this->authorize('view', $report);
        $report->load(['patient', 'request.requester:id,name', 'uploader', 'reviewer', 'visit', 'consultation']);

        return view('laboratory.reports.show', compact('report'));
    }

    /** Authorised streaming from the private disk. ?inline=1 for in-browser preview. */
    public function download(Request $request, LaboratoryReport $report, AuditLogger $audit): StreamedResponse
    {
        $this->authorize('download', $report);
        $disk = Storage::disk($report->file_disk);
        abort_unless($disk->exists($report->file_path), 404, 'The report file is missing from storage.');

        $inline = $request->boolean('inline') && $report->isPreviewable();
        $audit->log($inline ? 'previewed' : 'downloaded', $report, ($inline ? 'Previewed ' : 'Downloaded ').$report->report_number);

        $name = $report->report_number.'-'.preg_replace('/[^A-Za-z0-9._-]+/', '_', $report->original_filename);
        $headers = ['Content-Type' => $report->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private'];

        return $inline
            ? $disk->response($report->file_path, $name, $headers, 'inline')
            : $disk->download($report->file_path, $name, $headers);
    }

    public function review(Request $request, LaboratoryReport $report): RedirectResponse
    {
        $this->authorize('review', $report);
        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:5000']]);
        $this->lab->review($report, $request->user(), $data['review_notes'] ?? null);

        return back()->with('success', 'Report marked as reviewed.');
    }
}
