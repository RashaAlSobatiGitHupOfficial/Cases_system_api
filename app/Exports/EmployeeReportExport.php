<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;
    protected $columns;

    public function __construct($data, $columns)
    {
        $this->data = $data;
        $this->columns = $columns;
    }

    public function collection()
    {
        return collect($this->data);
    }

public function headings(): array
{
    $labels = [
        'name' => 'Employee',
        'total_cases' => 'Total Cases',
        'closed_cases' => 'Closed Cases',
        'completion_rate' => 'Completion %',
        'avg_first_response_time' => 'First Response (min)',
        'sla_rate' => 'SLA %',
        'performance_score' => 'Score'
    ];

    return array_values(
        array_intersect_key($labels, array_flip($this->columns))
    );
}

public function map($row): array
{
    $map = [
        'name' => $row['name'],
        'total_cases' => $row['total_cases'],
        'closed_cases' => $row['closed_cases'],
        'completion_rate' => $row['completion_rate'],
        'avg_first_response_time' => $row['avg_first_response_time'],
        'sla_rate' => $row['sla_rate'],
        'performance_score' => $row['performance_score']
    ];

    return array_values(
        array_intersect_key($map, array_flip($this->columns))
    );
}

}
