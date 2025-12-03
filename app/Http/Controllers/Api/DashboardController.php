<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\CaseModel;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    
    public function cards()
    {
        return response()->json([
            'total_employees' => User::count(),
            'total_clients'   => Client::count(),
            'total_cases'     => CaseModel::count(),
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
