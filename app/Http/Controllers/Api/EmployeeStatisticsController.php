<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\CaseModel;
use Carbon\Carbon;

class EmployeeStatisticsController extends Controller
{
   public function index(Request $request)
    {
        $range = $request->get('range', 'month');
        $dateRange = $this->getDateRange($range);

        $employees = Employee::with('user')->get();

        $results = [];

        // GLOBAL SLA COUNTERS
        $globalClosedCases = 0;
        $globalSlaBreached = 0;

        foreach ($employees as $emp) {

            if (!$emp->user_id) {
                continue;
            }

            $baseQuery = CaseModel::whereBetween('created_at', $dateRange)
                ->whereHas('employees', fn ($q) => $q->where('employee_id', $emp->id));

            $totalCases = $baseQuery->count();
            if ($totalCases === 0) continue;

            $closedCasesQuery = (clone $baseQuery)->where('status', 'closed');
            $closedCases = $closedCasesQuery->count();

            // Completion Rate
            $completionRate = round(($closedCases / $totalCases) * 100, 2);

            // First Response
            $avgFirstResponse = $closedCasesQuery
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

            // SLA per employee
            $employeeClosed = 0;
            $employeeBreached = 0;

            $closedCasesQuery->with(['priority', 'logs'])->get()->each(function ($case)
    use (&$employeeClosed, &$employeeBreached, &$globalClosedCases, &$globalSlaBreached) {

    if (!$case->priority) return;

    // 1️⃣ get real closed time from case_logs
    $closedLog = $case->logs
        ->where('action', 'closed')
        ->sortBy('created_at')
        ->first();

    if (!$closedLog) return;

    $employeeClosed++;
    $globalClosedCases++;

    // 2️⃣ calculate SLA duration in days (or hours)
   $durationHours = $case->created_at
    ->diffInHours($closedLog->created_at);

if ($durationHours > ($case->priority->delay_time * 24)) {
        $employeeBreached++;
        $globalSlaBreached++;
    }
});


            $slaRate = $employeeClosed > 0
                ? round((($employeeClosed - $employeeBreached) / $employeeClosed) * 100, 2)
                : 0;

            // Performance Score
            $responseScore = 100 - min($avgFirstResponse, 100);

            $performanceScore = round(
                ($completionRate * 0.4) +
                ($slaRate * 0.4) +
                ($responseScore * 0.2)
            );

            $results[] = [
                'name' => $emp->first_name . ' ' . $emp->last_name,
                'performance_score' => $performanceScore,
                'completion_rate' => $completionRate,
                'avg_first_response_time' => round($avgFirstResponse, 2),
                'sla_rate' => $slaRate,
            ];
            
        }
$highPriorityStats = [];

foreach ($employees as $emp) {

    if (!$emp->user_id) continue;

    $cases = CaseModel::whereBetween('created_at', $dateRange)
        ->whereHas('employees', fn ($q) => $q->where('employee_id', $emp->id))
        ->whereHas('priority', fn ($q) => $q->where('priority_name', 'High'))
        ->with(['priority', 'logs'])
        ->get();

    if ($cases->isEmpty()) continue;

    $closed = 0;
    $breached = 0;

    foreach ($cases as $case) {

        // get real close time from logs
        $closedLog = $case->logs
            ->where('action', 'closed')
            ->sortBy('created_at')
            ->first();

        if (!$closedLog || !$case->priority) continue;

        $closed++;

        $durationDays = $case->created_at
            ->startOfDay()
            ->diffInDays($closedLog->created_at->startOfDay());

        if ($durationDays > $case->priority->delay_time) {
            $breached++;
        }
    }

    if ($closed === 0) continue;

    $slaRate = round((($closed - $breached) / $closed) * 100, 2);

    $highPriorityStats[] = [
        'name' => $emp->first_name . ' ' . $emp->last_name,
        'sla_rate' => $slaRate,
        'closed_cases' => $closed,
        'breached_cases' => $breached
    ];




}
$highPriorityCollection = collect($highPriorityStats);

$bestHighPriority = $highPriorityCollection
    ->sortByDesc('sla_rate')
    ->first();

$worstHighPriority = $highPriorityCollection
    ->sortBy('sla_rate')
    ->first();


        $collection = collect($results);
$prevRange = $this->getPreviousDateRange($range);

$currentAvgPerformance = $collection->avg('performance_score') ?? 0;
$previousAvgPerformance = $this->calculateAveragePerformance($prevRange);

$trend = round($currentAvgPerformance - $previousAvgPerformance, 2);

        return response()->json([
            'stats' => [
                'highest' => $collection->sortByDesc('performance_score')->first(),
                'lowest'  => $collection->sortBy('performance_score')->first(),
                'best_completion' => $collection->sortByDesc('completion_rate')->first(),
                'best_sla' => $collection->sortByDesc('sla_rate')->first(),
                'fastest' => $collection->sortBy('avg_first_response_time')->first(),
                'slowest' => $collection->sortByDesc('avg_first_response_time')->first(),
                'worst_completion' => $collection->sortBy('completion_rate')->first(),

            ],

            'distribution' => [
                'high' => $collection->where('performance_score', '>=', 85)->count(),
                'medium' => $collection->whereBetween('performance_score', [60, 84])->count(),
                'low' => $collection->where('performance_score', '<', 60)->count(),
            ],

            'sla_breach' => [
                'breached' => $globalSlaBreached,
                'total' => $globalClosedCases,
                'rate' => $globalClosedCases > 0
                    ? round(($globalSlaBreached / $globalClosedCases) * 100, 2)
                    : 0
            ],

            'charts' => [
                'performance' => $collection->map(fn ($e) => [
                    'name' => $e['name'],
                    'value' => $e['performance_score']
                ])->values(),

                'response_time' => $collection->map(fn ($e) => [
                    'name' => $e['name'],
                    'value' => $e['avg_first_response_time']
                ])->values(),
                ],
                'priority_stats' => [
    'high' => [
        'best' => $bestHighPriority,
        'worst' => $worstHighPriority
    ]
],'trends' => [
    'performance' => [
        'current' => round($currentAvgPerformance, 2),
        'previous' => round($previousAvgPerformance, 2),
        'delta' => $trend
    ]
],'high_priority_sla' => $highPriorityCollection->map(fn ($e) => [
        'name' => $e['name'],
        'value' => $e['sla_rate']
    ])->values(),


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
            default:
                return [Carbon::now()->startOfMonth(), Carbon::now()];
        }
    }
    private function getPreviousDateRange($range)
{
    switch ($range) {
        case 'day':
            return [
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay()
            ];

        case 'week':
            return [
                Carbon::now()->subWeek()->startOfWeek(),
                Carbon::now()->subWeek()->endOfWeek()
            ];

        case 'year':
            return [
                Carbon::now()->subYear()->startOfYear(),
                Carbon::now()->subYear()->endOfYear()
            ];

        default: // month
            return [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth()
            ];
    }
}
private function calculateAveragePerformance($dateRange)
{
    $employees = Employee::with('user')->get();
    $scores = [];

    foreach ($employees as $emp) {
        if (!$emp->user_id) continue;

        $baseQuery = CaseModel::whereBetween('created_at', $dateRange)
            ->whereHas('employees', fn ($q) => $q->where('employee_id', $emp->id));

        $total = $baseQuery->count();
        if ($total === 0) continue;

        $closedQuery = (clone $baseQuery)->where('status', 'closed');
        $closed = $closedQuery->count();

        $completion = ($closed / $total) * 100;

        $avgResponse = $closedQuery->with('logs')->get()
            ->map(fn ($c) =>
                optional(
                    $c->logs->where('user_id', $emp->user_id)->sortBy('created_at')->first()
                )?->created_at
                    ? $c->created_at->diffInMinutes(
                        $c->logs->where('user_id', $emp->user_id)->first()->created_at
                    )
                    : null
            )->filter()->avg() ?? 0;

        $responseScore = 100 - min($avgResponse, 100);

        $scores[] = ($completion * 0.6) + ($responseScore * 0.4);
    }

    return collect($scores)->avg() ?? 0;
}

}
