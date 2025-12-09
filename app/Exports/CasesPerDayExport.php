<?php

namespace App\Exports;

use App\Models\CaseModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CasesPerDayExport implements FromCollection, WithHeadings
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
                'Date'  => $row->date,
                'Total Cases' => $row->total,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Total Cases',
        ];
    }
}
