<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates gap-tolerant, collision-free document numbers using a locked counter row.
 * Must be called inside the caller's transaction so the number is rolled back with it.
 */
class SequenceGenerator
{
    public function next(string $name, string $period = 'all'): int
    {
        return DB::transaction(function () use ($name, $period) {
            $row = DB::table('document_sequences')
                ->where('name', $name)->where('period', $period)
                ->lockForUpdate()->first();

            if (! $row) {
                DB::table('document_sequences')->insertOrIgnore([
                    'name' => $name, 'period' => $period, 'last_value' => 0,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $row = DB::table('document_sequences')
                    ->where('name', $name)->where('period', $period)
                    ->lockForUpdate()->first();
            }

            $next = $row->last_value + 1;
            DB::table('document_sequences')->where('id', $row->id)->update(['last_value' => $next, 'updated_at' => now()]);

            return $next;
        });
    }

    /** e.g. RX-2026-000123 */
    public function code(string $name, string $prefix, int $pad = 6): string
    {
        $year = now()->format('Y');

        return sprintf('%s-%s-%s', $prefix, $year, str_pad((string) $this->next($name, $year), $pad, '0', STR_PAD_LEFT));
    }

    /**
     * Patient IDs follow the configurable format, e.g. "{PREFIX}-{YEAR}-{SEQ}" => PAT-2026-00001.
     * The counter resets yearly only when the format contains {YEAR}.
     */
    public function patientCode(): string
    {
        $format = (string) settings('patient_id_format', '{PREFIX}-{YEAR}-{SEQ}');
        $prefix = (string) settings('patient_id_prefix', 'PAT');
        $pad = max(1, min(10, (int) settings('patient_id_padding', 5)));
        $year = now()->format('Y');
        $period = str_contains($format, '{YEAR}') ? $year : 'all';

        if (! str_contains($format, '{SEQ}')) {
            $format .= '-{SEQ}';
        }

        $seq = $this->next('patient', $period);

        return strtr($format, [
            '{PREFIX}' => $prefix,
            '{YEAR}' => $year,
            '{YY}' => now()->format('y'),
            '{MONTH}' => now()->format('m'),
            '{SEQ}' => str_pad((string) $seq, $pad, '0', STR_PAD_LEFT),
        ]);
    }
}
