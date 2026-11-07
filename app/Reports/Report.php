<?php

namespace App\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class Report
{
    public const MAX_ROWS = 10000;

    abstract public function key(): string;

    abstract public function title(): string;

    abstract public function description(): string;

    /** @return array<string, string> column key => heading */
    abstract public function columns(): array;

    /** @return Collection<int, array<string, scalar|null>> */
    abstract public function rows(array $filters): Collection;

    /** Which shared filters this report supports: doctor, patient, status, gender. */
    public function filters(): array
    {
        return ['doctor', 'patient'];
    }

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        return [];
    }

    /** Optional totals row. */
    public function totals(Collection $rows): ?array
    {
        return null;
    }

    protected function from(array $f): Carbon
    {
        return Carbon::parse($f['from'] ?? now()->startOfMonth())->startOfDay();
    }

    protected function to(array $f): Carbon
    {
        return Carbon::parse($f['to'] ?? now())->endOfDay();
    }

    protected function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    /** Portable "group by day/month" expression. */
    protected function dateBucket(string $column, string $grain): string
    {
        return match ($this->driver()) {
            'sqlite' => $grain === 'month' ? "strftime('%Y-%m', $column)" : "date($column)",
            'pgsql' => $grain === 'month' ? "to_char($column, 'YYYY-MM')" : "to_char($column, 'YYYY-MM-DD')",
            default => $grain === 'month' ? "DATE_FORMAT($column, '%Y-%m')" : "DATE($column)",
        };
    }

    protected function sumColumns(Collection $rows, array $numeric, string $labelKey): array
    {
        $out = [$labelKey => 'Total'];
        foreach ($numeric as $k) {
            $out[$k] = $rows->sum($k);
        }

        return $out;
    }
}
