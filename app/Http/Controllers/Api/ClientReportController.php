<?php

namespace App\Http\Controllers\Api;

use App\Exports\ClientReportExport;
use App\Http\Controllers\Controller;

use App\Models\Client;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ClientReportController extends Controller
{
   public function exportClientReport($clientId)
{
    // نفس الداتا التي تعيدها في show
    $clientData = $this->show(request(), $clientId)->getData(true);

    $clientName = $clientData['client']['client_name'] ?? 'client';

    $fileName = 'client-report-' . $clientName . '-' . now()->format('Y-m-d_His') . '.xlsx';

    return Excel::download(new ClientReportExport($clientData), $fileName);
}

    public function index(Request $request)
    {
        $clients = Client::withCount('cases')
            ->orderBy('client_name')
            ->get()
            ->map(function ($client) {
                return [
                    'id'          => $client->id,
                    'client_name' => $client->client_name,
                    'email'       => $client->email,
                    'address'     => $client->address,
                    'cases_count' => $client->cases_count,
                ];
            });

        return response()->json([
            'data' => $clients,
        ]);
    }

   
    public function show(Request $request, $clientId)
    {
        $client = Client::findOrFail($clientId);

        $baseQuery = CaseModel::query()
            ->where('client_id', $clientId)
            ->with(['priority', 'employees']);

        $totalCases = (clone $baseQuery)->count();

        $statusStats = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status'); 

        $statusKeys = ['opened', 'assigned', 'inprogress', 'reassigned', 'closed'];
        $statusStatsNormalized = [];
        foreach ($statusKeys as $key) {
            $statusStatsNormalized[$key] = (int) ($statusStats[$key] ?? 0);
        }

        $priorityStats = (clone $baseQuery)
            ->selectRaw('priority_id, COUNT(*) as total')
            ->groupBy('priority_id')
            ->with('priority')
            ->get()
            ->mapWithKeys(function ($row) {
                $name = optional($row->priority)->priority_name ?: 'Unknown';
                return [$name => (int) $row->total];
            });

        $latestCases = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($case) {
                return [
                    'id'         => $case->id,
                    'title'      => $case->title,
                    'status'     => $case->status,
                    'type'       => $case->type,
                    'way_entry'  => $case->way_entry,
                    'priority'   => optional($case->priority)->priority_name,
                    'created_at' => optional($case->created_at)->format('Y-m-d H:i'),
                    'employees'  => $case->employees
                        ? $case->employees->map(fn($e) => $e->first_name . ' ' . $e->last_name)->values()
                        : [],
                ];
            });

        $employees = (clone $baseQuery)
            ->with('employees')
            ->get()
            ->flatMap(function ($case) {
                return $case->employees;
            })
            ->unique('id')
            ->values()
            ->map(function ($e) {
                return [
                    'id'         => $e->id,
                    'full_name'  => $e->first_name . ' ' . $e->last_name,
                    'email'      => $e->email ?? null,
                ];
            });

        return response()->json([
            'client' => [
                'id'          => $client->id,
                'client_name' => $client->client_name,
                'email'       => $client->email,
                'address'     => $client->address,
            ],
            'stats' => [
                'total_cases' => $totalCases,
                'status'      => $statusStatsNormalized,
                'priority'    => $priorityStats,
            ],
            'latest_cases' => $latestCases,
            'team'         => $employees,
        ]);
    }
}
