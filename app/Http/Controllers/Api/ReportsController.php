<?php

namespace App\Http\Controllers\Api;

use App\Exports\CasesReportExport;
use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use App\Models\CaseEmployee;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function casesReport(Request $request)
    {
        $query = CaseModel::with(['client', 'employees', 'priority']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('way_entry')) {
            $query->where('way_entry', $request->way_entry);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('priority')) {
            $query->whereHas('priority', function ($q) use ($request) {
                $q->where('priority_name', 'LIKE', '%' . trim($request->priority) . '%');
            });
        }

        if ($request->filled('sort_by')) {
            $direction = $request->sort_direction === 'desc' ? 'desc' : 'asc';
            $query->orderBy($request->sort_by, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate(10)->through(function($case) {
            return $case->load(['client', 'priority', 'employees']);
        });
    }

    
   public function exportCases(Request $request)
{
   
    $query = $this->buildCasesQuery($request);

    
    $cases = $query->get();

    
    $visibleColumns = collect(
        explode(',', $request->get('visible_columns', ''))
    );

    
    if ($visibleColumns->isEmpty()) {
        $visibleColumns = collect([
            'id', 'title', 'type', 'way_entry', 'status',
            'priority', 'client', 'employees', 'created'
        ]);
    }

   
    $fileName = 'cases-report-' . now()->format('Y-m-d_His') . '.xlsx';

   
    return Excel::download(
        new CasesReportExport($cases, $visibleColumns),
        $fileName
    );
}


    /** Core query used by both table + export */
    private function buildCasesQuery(Request $request)
    {
        $query = CaseModel::query()
            ->with(['priority', 'client', 'employees']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        }
        // ====== Filters (must match what ReportFilters.vue sends) ======
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

          if ($request->filled('way_entry')) {
            $query->where('way_entry', $request->way_entry);
        }

         if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
         if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

       if ($request->filled('priority')) {
            $query->whereHas('priority', function ($q) use ($request) {
                $q->where('priority_name', 'LIKE', '%' . trim($request->priority) . '%');
            });
        }

        if ($request->filled('employee_id')) {
            $query->whereHas('employees', function ($q) use ($request) {
                $q->where('employees.id', $request->employee_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // ====== Sorting (title / status / priority like Vue) ======
        $sortBy        = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction', 'asc');

        if ($sortBy) {
            switch ($sortBy) {
                case 'priority':
                    // sort by priority name
                    $query->leftJoin('priorities', 'cases.priority_id', '=', 'priorities.id')
                          ->select('cases.*')
                          ->orderBy('priorities.priority_name', $sortDirection);
                    break;

                default:
                    $query->orderBy($sortBy, $sortDirection);
                    break;
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query;
    }

}
