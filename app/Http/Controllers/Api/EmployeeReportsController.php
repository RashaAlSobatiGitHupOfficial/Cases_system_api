<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\CaseModel;
use Carbon\Carbon;

class EmployeeReportsController extends Controller
{
    public function index(Request $request)
    {
        // ===========================================
        // 1) FLITER RANGE (default: month)
        // ===========================================
        $range = $request->get('range', 'month');

        $dateRange = $this->getDateRange($range);

        // ===========================================
        // 2) Get Employees with their user
        // ===========================================
        $employees = Employee::with('user')->get();

        $results = [];

        foreach ($employees as $emp) {
            $employeeId = $emp->id;

            // ===========================================
            // 3) Base Query for employee cases
            // ===========================================
            $baseQuery = CaseModel::whereBetween('created_at', $dateRange)
                ->whereHas('employees', function ($q) use ($employeeId) {
                    $q->where('employee_id', $employeeId);
                })
                ->with(['priority', 'logs']);

            // 3.1 Counts
            $totalCases = (clone $baseQuery)->count();
            $closedQuery = (clone $baseQuery)->where('status', 'closed');
            $closedCases = $closedQuery->count();

            // ===========================================
            // 4) Completion Rate
            // ===========================================
            $completionRate = $totalCases > 0
                ? round(($closedCases / $totalCases) * 100, 2)
                : 0;

            // ===========================================
            // 5) FIRST RESPONSE TIME (minutes)
            // ===========================================
            $firstResponseTimes = (clone $baseQuery)
                ->get()
                ->map(function ($case) use ($emp) {
                    // First log from this employee
                    $firstLog = $case->logs
                        ->where('user_id', $emp->user_id)
                        ->sortBy('created_at')
                        ->first();

                    if (!$firstLog) return null;

                    return abs($case->created_at->diffInMinutes($firstLog->created_at));
                })
                ->filter()
                ->values();

            $avgFirstResponse = $firstResponseTimes->avg() ?? 0;

            // ===========================================
            // 6) RESOLUTION TIME (hours)
            // ===========================================
            $resolutionTimes = $closedQuery
                ->get()
                ->map(function ($case) {
                    return $case->created_at->diffInHours($case->updated_at);
                });

            $avgResolutionTime = $resolutionTimes->avg() ?? 0;

            // ===========================================
            // 7) SLA RATE (based on priority delay_time)
            // ===========================================
            $slaRateRaw = $closedQuery->get()->map(function ($case) {

                if (!$case->priority) return true; // If no priority consider SLA met

                $daysToClose = $case->created_at->startOfDay()->diffInDays(
                        $case->updated_at->startOfDay()
                    );


                return $daysToClose <= $case->priority->delay_time;
            })->avg();

            $slaRate = $slaRateRaw ? round($slaRateRaw * 100, 2) : 0;


            // ===========================================
            // 8) PERFORMANCE SCORE
            // ===========================================

            // Response score (convert minutes to 0–100)
            $responseScore = 100 - min($avgFirstResponse, 100);

            $performanceScore = round(
                ($completionRate * 0.4) +
                ($slaRate * 0.4) +
                ($responseScore * 0.2)
            );


            // ===========================================
            // 9) Final result
            // ===========================================
            $results[] = [
                'id' => $emp->id,
                'name' => $emp->first_name . ' ' . $emp->last_name,
                'total_cases' => $totalCases,
                'closed_cases' => $closedCases,
                'completion_rate' => $completionRate,
                'avg_first_response_time' => round($avgFirstResponse, 2),
                'avg_resolution_time' => round($avgResolutionTime, 2),
                'sla_rate' => $slaRate,
                'performance_score' => $performanceScore
            ];
        }

        // ===========================================
        // 10) GLOBAL STATS
        // ===========================================
        $collection = collect($results);

        return response()->json([
            'stats' => [
                'highest'         => $collection->sortByDesc('performance_score')->first(),
                'lowest'          => $collection->sortBy('performance_score')->first(),
                'best_completion' => $collection->sortByDesc('completion_rate')->first(),
                'best_sla'        => $collection->sortByDesc('sla_rate')->first(),
                'fastest'         => $collection->sortBy('avg_first_response_time')->first(),
                'slowest'         => $collection->sortByDesc('avg_first_response_time')->first(),
            ],
            'data' => $results,
        ]);
    }

    private function getDateRange($range)
    {
        switch ($range) {
            case 'day':
                return [Carbon::today(), Carbon::now()];

            case 'week':
                return [Carbon::now()->startOfWeek(), Carbon::now()];

            case 'year':
                return [Carbon::now()->startOfYear(), Carbon::now()];

            default: // month
                return [Carbon::now()->startOfMonth(), Carbon::now()];
        }
    }
}
