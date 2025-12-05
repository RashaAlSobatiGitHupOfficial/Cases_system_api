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

    $queryUsers = User::query();
    $queryClients = Client::query();
    $queryCases = CaseModel::query();

    switch ($range) {
        case 'day':
            $queryUsers->whereDate('created_at', today());
            $queryClients->whereDate('created_at', today());
            $queryCases->whereDate('created_at', today());
            break;

        case 'week':
            $start = now()->startOfWeek();
            $end = now()->endOfWeek();
            $queryUsers->whereBetween('created_at', [$start, $end]);
            $queryClients->whereBetween('created_at', [$start, $end]);
            $queryCases->whereBetween('created_at', [$start, $end]);
            break;

        case 'year':
            $queryUsers->whereYear('created_at', now()->year);
            $queryClients->whereYear('created_at', now()->year);
            $queryCases->whereYear('created_at', now()->year);
            break;

        case 'month':
        default:
            $queryUsers->whereMonth('created_at', now()->month);
            $queryClients->whereMonth('created_at', now()->month);
            $queryCases->whereMonth('created_at', now()->month);
            break;
    }

    return response()->json([
        'total_employees' => $queryUsers->count(),
        'total_clients'   => $queryClients->count(),
        'total_cases'     => $queryCases->count(),
    ]);
}



 
    public function casesPerDay()
    {
        $data = CaseModel::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
        ->where('created_at', '>=', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return response()->json($data);
    }



    public function casesByStatus()
    {
        $data = CaseModel::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        return response()->json($data);
    }


   
   public function casesByPriority()
    {
        $data = CaseModel::with('priority')
            ->select('priority_id', DB::raw('COUNT(*) as total'))
            ->groupBy('priority_id')
            ->get()
            ->map(function ($item) {
                return [
                    'priority' => $item->priority->priority_name ?? "Unknown",
                    'total' => $item->total,
                ];
            });

        return response()->json($data);
    }


    
    public function casesByType()
    {
        $data = CaseModel::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->get();

        return response()->json($data);
    }
 

    
   public function topClients()
    {
        $data = Client::withCount('cases')
            ->orderBy('cases_count', 'desc')
            ->take(5)
            ->get();

        return response()->json($data);
    }

    
    public function completionRate()
    {
        $total = CaseModel::count();
        $closed = CaseModel::where('status', 'closed')->count();

        return response()->json([
            'completion_rate' => $total > 0 ? round(($closed / $total) * 100, 2) : 0
        ]);
    }
}
