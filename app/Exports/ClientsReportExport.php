<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsReportExport implements FromCollection, WithHeadings
{
    protected $rows;
    protected $columns;

    public function __construct(Collection $rows, array $columns)
    {
        $this->rows = $rows;
        $this->columns = $columns;
    }

    public function collection()
    {
        return $this->rows->map(function ($row) {
            $data = [];

            foreach ($this->columns as $col) {
                $data[$col] = $row->$col ?? null;
            }

            return $data;
        });
    }

    public function headings(): array
    {
        return array_map(fn ($c) => ucfirst(str_replace('_', ' ', $c)), $this->columns);
    }
}

