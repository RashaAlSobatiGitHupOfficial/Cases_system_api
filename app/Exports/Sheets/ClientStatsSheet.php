<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClientStatsSheet implements FromArray, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data['stats'];
    }

    public function array(): array
    {
        $rows = [
            ['Stats'],
            ['Total Cases', $this->data['total_cases']],
            [],
            ['Status Breakdown'],
        ];

        foreach ($this->data['status'] as $k => $v) {
            $rows[] = [$k, $v];
        }

        $rows[] = [];
        $rows[] = ['Priority Breakdown'];

        foreach ($this->data['priority'] as $k => $v) {
            $rows[] = [$k, $v];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Client Status';   
    }
}
