<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClientLatestCasesSheet implements FromArray, WithHeadings, WithTitle
{
    protected $cases;

    public function __construct($data)
    {
        $this->cases = $data['latest_cases'];
    }

    public function headings(): array
    {
        return [
            'ID', 'Title', 'Status', 'Priority', 'Created At', 'Employees'
        ];
    }

    public function array(): array
    {
        return array_map(function ($case) {
            return [
                $case['id'],
                $case['title'],
                $case['status'],
                $case['priority'] ?? '—',
                $case['created_at'],
                implode(', ', $case['employees'] ?? []),
            ];
        }, $this->cases);
    }

    public function title(): string
    {
        return 'Client Latest Cases';   
    }
}
