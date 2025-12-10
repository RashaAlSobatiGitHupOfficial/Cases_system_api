<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientTeamSheet implements FromArray, WithHeadings
{
    protected $team;

    public function __construct($data)
    {
        $this->team = $data['team'];
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email'];
    }

    public function array(): array
    {
        return array_map(function ($e) {
            return [
                $e['id'],
                $e['full_name'],
                $e['email'] ?? '—',
            ];
        }, $this->team);
    }
}
