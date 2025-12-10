<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CasesReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected Collection $cases;
    protected $visibleColumns;

    public function __construct(Collection $cases, $visibleColumns)
    {
        $this->cases = $cases;
        $this->visibleColumns = $visibleColumns;
    }

    public function collection()
    {
        return $this->cases;
    }

    public function headings(): array
{
    $all = [
        'id' => 'ID',
        'title' => 'Title',
        'type' => 'Type',
        'way_entry' => 'Way Entry',
        'status' => 'Status',
        'priority' => 'Priority',
        'client' => 'Client',
        'employees' => 'Employees',
        'created' => 'Created At',
    ];

    return $this->visibleColumns->map(fn($key) => $all[$key])->toArray();
}

    public function map($case): array
{
    $values = [
        'id' => $case->id,
        'title' => $case->title,
        'type' => $case->type,
        'way_entry' => $case->way_entry,
        'status' => $case->status,
        'priority' => optional($case->priority)->priority_name,
        'client' => optional($case->client)->client_name,
        'employees' => $case->employees
            ? $case->employees->map(fn($e) => $e->first_name . ' ' . $e->last_name)->implode(', ')
            : null,
        'created' => optional($case->created_at)->format('Y-m-d H:i'),
    ];

    return $this->visibleColumns->map(fn($key) => $values[$key])->toArray();
}

}
