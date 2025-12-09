<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CasesPerStatusExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($row) {
            return [
                'Case Status' => $row->status,
                'Total Cases' => $row->total,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Case Status',
            'Total Cases',
        ];
    }
}
