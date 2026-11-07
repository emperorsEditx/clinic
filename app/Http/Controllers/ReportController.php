<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Patient;
use App\Models\User;
use App\Reports\Report;
use App\Reports\ReportRegistry;
use App\Services\AuditLogger;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private ReportRegistry $registry) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return view('reports.index', ['reports' => $this->registry->all()]);
    }

    public function show(Request $request, string $key): View
    {
        [$report, $filters] = $this->resolve($request, $key);
        $rows = $report->rows($filters);
        $page = max(1, (int) $request->query('page', 1));
        $paginated = new LengthAwarePaginator($rows->forPage($page, 50)->values(), $rows->count(), 50, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('reports.show', [
            'report' => $report, 'filters' => $filters, 'rows' => $paginated, 'totals' => $report->totals($rows),
            'doctors' => User::withTrashed()->role(User::ROLE_DOCTOR)->orderBy('name')->get(['id', 'name']),
            'patient' => ! empty($filters['patient']) ? Patient::withTrashed()->find($filters['patient']) : null,
        ]);
    }

    public function export(Request $request, string $key, string $format, PdfService $pdf, AuditLogger $audit): Response
    {
        [$report, $filters] = $this->resolve($request, $key);
        $rows = $report->rows($filters);
        $audit->log('exported', null, "Exported {$report->title()} ($format)", ['filters' => $filters, 'rows' => $rows->count()]);
        $file = $report->key().'-'.now()->format('Ymd-His');

        return match ($format) {
            'xlsx' => Excel::download(new ReportExport($report, $rows), $file.'.xlsx'),
            'csv' => Excel::download(new ReportExport($report, $rows), $file.'.csv', \Maatwebsite\Excel\Excel::CSV),
            'pdf' => $pdf->report($report, $rows, $filters, $file.'.pdf'),
            'print' => response()->view('reports.print', ['report' => $report, 'rows' => $rows, 'filters' => $filters, 'totals' => $report->totals($rows)]),
            default => abort(404),
        };
    }

    /** @return array{0: Report, 1: array<string, mixed>} */
    private function resolve(Request $request, string $key): array
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $report = $this->registry->find($key) ?? abort(404);
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'doctor' => ['nullable', 'integer'],
            'patient' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:40'],
            'gender' => ['nullable', 'string', 'max:20'],
        ]);
        $filters['from'] ??= now()->startOfMonth()->toDateString();
        $filters['to'] ??= now()->toDateString();

        return [$report, $filters];
    }
}
