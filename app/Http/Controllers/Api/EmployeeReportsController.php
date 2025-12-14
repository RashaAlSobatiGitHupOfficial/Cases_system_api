<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\CaseModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Exports\EmployeeReportExport;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeReportsController extends Controller
{
    /* =====================================================
     | INDEX (API TABLE)
     ===================================================== */
    public function index(Request $request)
    {
        $range   = $request->get('range', 'month');
        $perPage = (int) $request->get('per_page', 10);
        $page    = (int) $request->get('page', 1);

        $dateRange = $this->getDateRange($range);

        $results = $this->buildEmployeeReportData($request, $dateRange);

        return response()->json(
            $this->paginateCollection($results, $perPage, $page)
        );
    }

    /* =====================================================
     | EXPORT (EXCEL)
     ===================================================== */
    public function export(Request $request)
    {
        $range = $request->get('range', 'month');
        $dateRange = $this->getDateRange($range);

        $visibleColumns = array_filter(
            explode(',', $request->get('visible_columns', ''))
        );

        $data = $this->buildEmployeeReportData($request, $dateRange)
            ->map(fn ($row) => collect($row)->only($visibleColumns)->toArray())
            ->values();

        return Excel::download(
            new EmployeeReportExport($data->toArray(), $visibleColumns),
            'employees-report.xlsx'
        );
    }

    /* =====================================================
     | CORE REPORT LOGIC (SHARED)
     ===================================================== */
    private function buildEmployeeReportData(Request $request, array $dateRange): Collection
    {
        $employeeName = $request->get('employee');
        $performance  = $request->get('performance'); // high | medium | low
        $slaFilter    = $request->get('sla');          // met | breached

        $employees = Employee::with('user')
            ->when($employeeName, function ($q) use ($employeeName) {
                $q->whereRaw(
                    "CONCAT(first_name,' ',last_name) LIKE ?",
                    ['%' . $employeeName . '%']
                );
            })
            ->get();

        $results = collect();

        foreach ($employees as $emp) {

            if (!$emp->user_id) continue;

            $baseQuery = CaseModel::whereBetween('created_at', $dateRange)
                ->whereHas('employees', fn ($q) =>
                    $q->where('employee_id', $emp->id)
                );

            $totalCases = $baseQuery->count();
            if ($totalCases === 0) continue;

            /* ================= CLOSED ================= */
            $closedQuery = (clone $baseQuery)->where('status', 'closed');
            $closedCases = $closedQuery->count();

            /* ================= COMPLETION ================= */
            $completionRate = round(($closedCases / $totalCases) * 100, 2);

            /* ================= FIRST RESPONSE ================= */
            $avgFirstResponse = $closedQuery
                ->with('logs')
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
                ->filter()
                ->avg() ?? 0;

            /* ================= SLA ================= */
            $closedCount   = 0;
            $breachedCount = 0;

            $closedQuery->with(['priority', 'logs'])->get()
                ->each(function ($case) use (&$closedCount, &$breachedCount) {

                    if (!$case->priority) return;

                    $closedLog = $case->logs
                        ->where('action', 'closed')
                        ->sortBy('created_at')
                        ->first();

                    if (!$closedLog) return;

                    $closedCount++;

                    $hours = $case->created_at
                        ->diffInHours($closedLog->created_at);

                    if ($hours > ($case->priority->delay_time * 24)) {
                        $breachedCount++;
                    }
                });

            $slaRate = $closedCount > 0
                ? round((($closedCount - $breachedCount) / $closedCount) * 100, 2)
                : 0;

            /* ================= PERFORMANCE SCORE ================= */
            $responseScore = 100 - min($avgFirstResponse, 100);

            $performanceScore = round(
                ($completionRate * 0.4) +
                ($slaRate * 0.4) +
                ($responseScore * 0.2)
            );

            $results->push([
                'id'                      => $emp->id,
                'name'                    => $emp->first_name . ' ' . $emp->last_name,
                'total_cases'             => $totalCases,
                'closed_cases'            => $closedCases,
                'completion_rate'         => $completionRate,
                'avg_first_response_time' => round($avgFirstResponse, 2),
                'sla_rate'                => $slaRate,
                'performance_score'       => $performanceScore,
            ]);
        }

        /* ================= FILTERS ================= */
        return $results
            ->when($performance === 'high', fn ($c) =>
                $c->where('performance_score', '>=', 85)
            )
            ->when($performance === 'medium', fn ($c) =>
                $c->whereBetween('performance_score', [60, 84])
            )
            ->when($performance === 'low', fn ($c) =>
                $c->where('performance_score', '<', 60)
            )
            ->when($slaFilter === 'met', fn ($c) =>
                $c->where('sla_rate', '>', 0)
            )
            ->when($slaFilter === 'breached', fn ($c) =>
                $c->where('sla_rate', '=', 0)
            )
            ->values();
    }

    /* =====================================================
     | HELPERS
     ===================================================== */
    private function paginateCollection(Collection $items, int $perPage, int $page): array
    {
        $total = $items->count();

        return [
            'data' => $items
                ->slice(($page - 1) * $perPage, $perPage)
                ->values(),
            'meta' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
            ]
        ];
    }

    private function getDateRange(string $range): array
    {
        return match ($range) {
            'day'  => [Carbon::today(), Carbon::now()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()],
        };
    }
}
