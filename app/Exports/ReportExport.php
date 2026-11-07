<?php

namespace App\Exports;

use App\Reports\Report;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private Report $report, private Collection $rows) {}

    public function array(): array
    {
        $keys = array_keys($this->report->columns());
        $data = $this->rows->map(fn ($r) => array_map(fn ($k) => $r[$k] ?? null, $keys))->all();
        if ($totals = $this->report->totals($this->rows)) {
            $data[] = array_map(fn ($k) => $totals[$k] ?? null, $keys);
        }

        return $data;
    }

    public function headings(): array
    {
        return array_values($this->report->columns());
    }

    public function title(): string
    {
        return substr($this->report->title(), 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
