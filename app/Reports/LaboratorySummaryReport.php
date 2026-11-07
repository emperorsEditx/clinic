<?php

namespace App\Reports;

use App\Models\LaboratoryRequest;
use Illuminate\Support\Collection;

class LaboratorySummaryReport extends Report
{
    public function key(): string
    {
        return 'laboratory-summary';
    }

    public function title(): string
    {
        return 'Laboratory Reports Summary';
    }

    public function description(): string
    {
        return 'Lab requests per test with their progress.';
    }

    public function filters(): array
    {
        return ['doctor', 'patient'];
    }

    public function columns(): array
    {
        return ['test' => 'Test', 'category' => 'Category', 'total' => 'Requested', 'pending' => 'In progress', 'completed' => 'Awaiting review', 'reviewed' => 'Reviewed', 'cancelled' => 'Cancelled'];
    }

    public function rows(array $f): Collection
    {
        return LaboratoryRequest::query()
            ->selectRaw("test_name, test_category, COUNT(*) as total,
                SUM(CASE WHEN status IN ('requested','sample_collected','processing') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            ->whereBetween('created_at', [$this->from($f), $this->to($f)])
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('requested_by', $d))
            ->when($f['patient'] ?? null, fn ($q, $p) => $q->where('patient_id', $p))
            ->groupBy('test_name', 'test_category')->orderByDesc('total')->get()
            ->map(fn ($r) => [
                'test' => $r->test_name, 'category' => $r->test_category, 'total' => (int) $r->total,
                'pending' => (int) $r->pending, 'completed' => (int) $r->completed,
                'reviewed' => (int) $r->reviewed, 'cancelled' => (int) $r->cancelled,
            ]);
    }

    public function totals(Collection $rows): ?array
    {
        return $this->sumColumns($rows, ['total', 'pending', 'completed', 'reviewed', 'cancelled'], 'test') + ['category' => ''];
    }
}
