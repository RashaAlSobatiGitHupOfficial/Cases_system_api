<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function casesReport(Request $request)
    {
        try {
            $query = CaseModel::with(['client', 'priority', 'employees']);

            /* ============================================================
             *                          SEARCH
             * ============================================================ */
            if ($request->filled('search')) {
                $search = trim($request->search);

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            /* ============================================================
             *                        CLIENT FILTER
             * ============================================================ */
            if ($request->filled('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            /* ============================================================
             *                     PRIORITY (BY NAME)
             * ============================================================ */
            if ($request->filled('priority')) {
                $priority = trim($request->priority);

                $query->whereHas('priority', function ($q) use ($priority) {
                    $q->where('priority_name', 'LIKE', "%{$priority}%");
                });
            }

            /* ============================================================
             *                 ASSIGNED EMPLOYEE FILTER
             * ============================================================ */
            if ($request->filled('employee_id')) {
                $employeeId = $request->employee_id;

                $query->whereHas('employees', function ($q) use ($employeeId) {
                    $q->where('employees.id', $employeeId);
                });
            }

            /* ============================================================
             *                         STATUS FILTER
             * ============================================================ */
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            /* ============================================================
             *                         DATE RANGE
             * ============================================================ */
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            /* ============================================================
             *                           SORTING
             * ============================================================ */
            if ($request->filled('sort_by')) {
                $direction = $request->sort_direction === 'desc' ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $direction);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            /* ============================================================
             *                          PAGINATION
             * ============================================================ */
            $cases = $query->paginate(10);

            /* ============================================================
             *                     SUMMARY STATISTICS
             * ============================================================ */
            $stats = [
                'total'      => CaseModel::count(),
                'opened'     => CaseModel::where('status', 'opened')->count(),
                'assigned'   => CaseModel::where('status', 'assigned')->count(),
                'inprogress' => CaseModel::where('status', 'in_progress')->count(),
                'reassigned' => CaseModel::where('status', 'reassigned')->count(),
                'closed'     => CaseModel::where('status', 'closed')->count(),
            ];

            /* ============================================================
             *                     RETURN JSON RESPONSE
             * ============================================================ */
            return response()->json([
                'data'      => $cases->items(),
                'stats'     => $stats,
                'total'     => $cases->total(),
                'last_page' => $cases->lastPage()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /* ============================================================
     *                  EXPORT (OPTIONAL IF YOU WANT)
     * ============================================================ */
    public function exportCases(Request $request)
    {
        // You can add CSV export here later.
        return response()->json(['message' => 'CSV export not implemented'], 200);
    }
}
