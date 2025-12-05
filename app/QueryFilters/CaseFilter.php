<?php

namespace App\QueryFilters;

class CaseFilter
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function apply($query)
    {
        if ($this->request->search) {
            $query->where(function($q) {
                $q->where('title', 'LIKE', "%{$this->request->search}%")
                  ->orWhere('description', 'LIKE', "%{$this->request->search}%");
            });
        }

        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }

        if ($this->request->tab === 'mine') {
            $emp = $this->request->user()->employee->id;
            $query->whereHas('employees', fn($q) => $q->where('employee_id', $emp));
        }

        if ($this->request->tab === 'unassigned') {
            $query->whereDoesntHave('employees');
        }

        return $query;
    }
}
