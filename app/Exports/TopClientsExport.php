<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TopClientsExport implements FromCollection, WithHeadings
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
                'Client Name' => $row->client_name,
                'Total Cases' => $row->cases_count,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Client Name',
            'Total Cases',
        ];
    }
}
