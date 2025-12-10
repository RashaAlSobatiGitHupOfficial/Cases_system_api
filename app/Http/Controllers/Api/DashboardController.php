<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\CaseModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    

    public function cards(Request $request)
    {
        $range = $request->query('range', 'month');
        return response()->json([
            'total_employees' => $this->filterRange(User::query(), $range)->count(),
            'total_clients'   => $this->filterRange(Client::query(), $range)->count(),
            'total_cases'     => $this->filterRange(CaseModel::query(), $range)->count(),
        ]);
    }

    public function casesPerDay(Request $request)
    {
        $range = $request->query('range', 'month');

        $query = CaseModel::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )->groupBy('date')->orderBy('date');

        $query = $this->filterRange($query, $range);

        return response()->json($query->get());
    }

    public function casesByStatus(Request $request)
    {
        $range = $request->query('range', 'month');

        $query = CaseModel::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status');

        $query = $this->filterRange($query, $range);

        return response()->json($query->get());
    }

    public function casesByPriority(Request $request)
    {
        $range = $request->query('range', 'month');

        $query = CaseModel::with('priority')
            ->select('priority_id', DB::raw('COUNT(*) as total'))
            ->groupBy('priority_id');

        $query = $this->filterRange($query, $range);

        return response()->json(
            $query->get()->map(fn ($item) => [
                'priority' => $item->priority->priority_name ?? "Unknown",
                'total' => $item->total
            ])
        );
    }

    public function casesByType(Request $request)
    {
        $range = $request->query('range', 'month');

        $query = CaseModel::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type');

        $query = $this->filterRange($query, $range);

        return response()->json($query->get());
    }

    public function topClients(Request $request)
    {
        $range = $request->query('range', 'month');

        $query = Client::withCount(['cases' => function ($q) use ($range) {
            $this->filterRange($q, $range);
        }])->orderBy('cases_count', 'desc')->take(5);

        return response()->json($query->get());
    }

    public function completionRate(Request $request)
    {
        $range = $request->query('range', 'month');

        $total = $this->filterRange(CaseModel::query(), $range)->count();
        $closed = $this->filterRange(
            CaseModel::where('status', 'closed'), 
            $range
        )->count();

        return response()->json([
            'completion_rate' => $total > 0 ? round(($closed / $total) * 100, 2) : 0
        ]);
    }

    private function filterRange($query, $range)
    {
        switch ($range) {
            case 'day':
                return $query->whereDate('created_at', today());
            case 'week':
                return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            case 'year':
                return $query->whereYear('created_at', now()->year);
            case 'month':
            default:
                return $query->whereMonth('created_at', now()->month);
        }
    }

}
