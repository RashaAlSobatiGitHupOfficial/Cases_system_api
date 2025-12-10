<?php

namespace App\Http\Controllers\Api;

use App\Exports\CasesPerDayExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CasesPerPriorityExport;
use App\Exports\CasesPerStatusExport;
use App\Exports\CasesPerTypeExport;
use App\Exports\TopClientsExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Client;
use App\Models\CaseModel;

class DashboardExportExcelController extends Controller
{
   public function exportCasesPerPriority (Request $request)
    {
        $range = $request->query('range', 'month');

        $query = CaseModel::with('priority')
            ->select('priority_id', DB::raw('COUNT(*) as total'))
            ->groupBy('priority_id');

        $query = $this->filterRange($query, $range);

         $data = $query->get();
        return Excel::download(new CasesPerPriorityExport($data), 'cases-per-priority.xlsx');

    }
    public function exportCasesPerType(Request $request)  {
        $range = $request->query('range', 'month');

        $query = CaseModel::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type');

        $query = $this->filterRange($query, $range);
        $data = $query->get();
        return Excel::download(new CasesPerTypeExport($data), 'cases-per-type.xlsx');

    }

    public function exportTopClients (Request $request)
    {
       $range = $request->query('range', 'month');

        $query = Client::withCount(['cases' => function ($q) use ($range) {
            $this->filterRange($q, $range);
        }])->orderBy('cases_count', 'desc')->take(5);
        $data = $query->get();
        return Excel::download(new TopClientsExport($data), 'top-cleints.xlsx');

    }
    public function exportCasesPerStatus ( Request $request)
    {
     $range = $request->query('range', 'month');

        $query = CaseModel::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status');

        $query = $this->filterRange($query, $range);
        $data = $query->get();
        return Excel::download(new CasesPerStatusExport($data), 'cases-per-status.xlsx');

    }
    public function exportCasesPerDay(Request $request)
    {
        $range = $request->query('range');

        $data = CaseModel::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->when($range, function ($q) use ($range) {
                if ($range === '7') {
                    $q->where('created_at', '>=', now()->subDays(7));
                }
                elseif ($range === '30') {
                    $q->where('created_at', '>=', now()->subDays(30));
                }
            })
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Excel::download(new CasesPerDayExport($data), 'cases-per-day.xlsx');
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
