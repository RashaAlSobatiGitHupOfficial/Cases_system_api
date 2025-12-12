<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\CaseModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeeReportsController extends Controller
{
    public function index(Request $request)
    {
        /* ==============================
         * 1) Filters
         * ============================== */
        $range        = $request->get('range', 'month');
        $employeeName = $request->get('employee');
        $slaFilter    = $request->get('sla');          // met | breached
        $performance  = $request->get('performance'); // high | medium | low
        $completion   = $request->get('completion');  // high | mid | low
        $perPage      = $request->get('per_page', 10);

        $dateRange = $this->getDateRange($range);

        /* ==============================
         * 2) Base employees
         * ============================== */
        $employees = Employee::with(['user', 'cases.priority', 'cases.logs'])
            ->when($employeeName, function ($q) use ($employeeName) {
                $q->whereRaw(
                    "CONCAT(first_name,' ',last_name) LIKE ?",
                    ['%' . $employeeName . '%']
                );
            })
            ->get();

        /* ==============================
         * 3) Build metrics per employee
         * ============================== */
        $results = $employees->map(function ($emp) use ($dateRange) {

            $baseQuery = CaseModel::whereBetween('created_at', $dateRange)
                ->whereHas('employees', fn ($q) =>
                    $q->where('employee_id', $emp->id)
                );

            $totalCases  = $baseQuery->count();
            $closedCases = (clone $baseQuery)->where('status', 'closed')->count();

            $completionRate = $totalCases > 0
                ? round(($closedCases / $totalCases) * 100, 2)
                : 0;

            /* ===== First Response ===== */
            $firstResponses = (clone $baseQuery)->where('status', 'closed')
                ->get()
                ->map(function ($case) use ($emp) {
                    $log = $case->logs
                        ->where('user_id', $emp->user_id)
                        ->sortBy('created_at')
                        ->first();

                    return $log
                        ? $case->created_at->diffInMinutes($log->created_at)
                        : null;
                })
                ->filter();

            $avgFirstResponse = round($firstResponses->avg() ?? 0, 2);

            /* ===== Resolution Time ===== */
            $resolutionTimes = (clone $baseQuery)->where('status', 'closed')
                ->get()
                ->map(function ($case) {
                    $accepted = $case->logs->where('action', 'case_accepted')->first();
                    $closed   = $case->logs->where('action', 'closed')->first();

                    return ($accepted && $closed)
                        ? $accepted->created_at->diffInHours($closed->created_at)
                        : null;
                })
                ->filter();

            $avgResolution = round($resolutionTimes->avg() ?? 0, 2);

            /* ===== SLA ===== */
            $slaRate = (clone $baseQuery)->where('status', 'closed')
                ->get()
                ->map(function ($case) {
                    if (!$case->priority) return true;

                    $days = $case->created_at->diffInDays($case->updated_at);
                    return $days <= $case->priority->delay_time;
                })
                ->avg();

            $slaRate = $slaRate ? round($slaRate * 100, 2) : 0;

            /* ===== Performance Score ===== */
            $responseScore = 100 - min($avgFirstResponse, 100);

            $performanceScore = round(
                ($completionRate * 0.4) +
                ($slaRate * 0.4) +
                ($responseScore * 0.2)
            );

            return [
                'id'                       => $emp->id,
                'name'                     => $emp->first_name . ' ' . $emp->last_name,
                'total_cases'              => $totalCases,
                'closed_cases'             => $closedCases,
                'completion_rate'          => $completionRate,
                'avg_first_response_time'  => $avgFirstResponse,
                'avg_resolution_time'      => $avgResolution,
                'sla_rate'                 => $slaRate,
                'performance_score'        => $performanceScore,
            ];
        });

        /* ==============================
         * 4) Apply filters (collection)
         * ============================== */
        $results = $results
            ->when($slaFilter === 'met', fn ($c) => $c->where('sla_rate', '>', 0))
            ->when($slaFilter === 'breached', fn ($c) => $c->where('sla_rate', '=', 0))

            ->when($performance === 'high', fn ($c) => $c->where('performance_score', '>=', 80))
            ->when($performance === 'medium', fn ($c) =>
                $c->whereBetween('performance_score', [50, 79])
            )
            ->when($performance === 'low', fn ($c) => $c->where('performance_score', '<', 50))

            ->when($completion === 'high', fn ($c) => $c->where('completion_rate', '>=', 90))
            ->when($completion === 'mid', fn ($c) =>
                $c->whereBetween('completion_rate', [70, 89])
            )
            ->when($completion === 'low', fn ($c) => $c->where('completion_rate', '<', 70));

        /* ==============================
         * 5) Server-side pagination
         * ============================== */
        $page     = $request->get('page', 1);
        $paginated = $this->paginateCollection($results, $perPage, $page);

        return response()->json($paginated);
    }

    /* ==============================
     * Helpers
     * ============================== */
    private function paginateCollection(Collection $items, $perPage, $page)
    {
        $total = $items->count();
        $data  = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => (int) $page,
                'per_page'     => (int) $perPage,
                'total'        => $total,
                'last_page'    => ceil($total / $perPage),
            ]
        ];
    }

    private function getDateRange($range)
    {
        return match ($range) {
            'day'  => [Carbon::today(), Carbon::now()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()],
        };
    }
}
