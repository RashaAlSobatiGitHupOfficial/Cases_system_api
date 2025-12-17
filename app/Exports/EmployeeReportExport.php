<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EmployeeReportExport implements FromArray, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $rows;
    protected array $visibleColumns;

    public function __construct(array $rows, array $visibleColumns)
    {
        $this->rows = $rows;
        $this->visibleColumns = $visibleColumns;
    }

    /* ================= DATA ================= */
    public function array(): array
    {
        return $this->rows;
    }

    /* ================= HEADERS ================= */
    public function headings(): array
    {
        return array_map(
            fn ($col) => ucwords(str_replace('_', ' ', $col)),
            $this->visibleColumns
        );
    }

    /* ================= MAP (IMPORTANT) ================= */
    public function map($row): array
    {
        // 👈 row is ALREADY numeric
        return $row;
    }
}
