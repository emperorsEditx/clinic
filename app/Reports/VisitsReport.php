<?php

namespace App\Reports;

use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VisitsReport extends Report
{
    public function __construct(private string $grain = 'day') {}

    public function key(): string
    {
        return $this->grain === 'month' ? 'monthly-visits' : 'daily-visits';
    }

    public function title(): string
    {
        return $this->grain === 'month' ? 'Monthly Patient Visits' : 'Daily Patient Visits';
    }

    public function description(): string
    {
        return 'Visit volume per '.$this->grain.', split by outcome.';
    }

    public function filters(): array
    {
        return ['doctor'];
    }

    public function columns(): array
    {
        return ['period' => $this->grain === 'month' ? 'Month' : 'Date', 'total' => 'Total visits', 'completed' => 'Completed', 'open' => 'Open', 'cancelled' => 'Cancelled', 'patients' => 'Unique patients'];
    }

    public function rows(array $f): Collection
    {
        $bucket = $this->dateBucket('visit_at', $this->grain);

        return Visit::query()
            ->selectRaw("$bucket as period, COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ('waiting','in_consultation') THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                COUNT(DISTINCT patient_id) as patients")
            ->whereBetween('visit_at', [$this->from($f), $this->to($f)])
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->groupByRaw($bucket)->orderByRaw($bucket)->get()
            ->map(fn ($r) => [
                'period' => $this->grain === 'month' ? Carbon::createFromFormat('Y-m', $r->period)->format('M Y') : fmt_date($r->period),
                'total' => (int) $r->total, 'completed' => (int) $r->completed, 'open' => (int) $r->open,
                'cancelled' => (int) $r->cancelled, 'patients' => (int) $r->patients,
            ]);
    }

    public function totals(Collection $rows): ?array
    {
        return $this->sumColumns($rows, ['total', 'completed', 'open', 'cancelled'], 'period') + ['patients' => '—'];
    }
}
