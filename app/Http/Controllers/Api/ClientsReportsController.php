<?php

namespace App\Http\Controllers\Api;

use App\Exports\ClientsReportExport;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ClientsReportsController extends Controller
{
    public function export(Request $request)
{
    $search  = $request->get('search');
    $columns = $request->get('columns', []);

    if (empty($columns)) {
        abort(400, 'No columns selected');
    }

    $query = Client::query()
        ->leftJoin('cases', 'clients.id', '=', 'cases.client_id')
        ->leftJoin('case_employees', 'cases.id', '=', 'case_employees.case_id')
        ->when($search, function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('clients.client_name', 'LIKE', "%{$search}%")
                    ->orWhere('clients.email', 'LIKE', "%{$search}%")
                    ->orWhere('clients.address', 'LIKE', "%{$search}%");
            });
        })
        ->select(
            'clients.client_name',
            'clients.email',
            'clients.address',
            DB::raw('COUNT(DISTINCT cases.id) as total_cases'),
            DB::raw('COUNT(DISTINCT case_employees.employee_id) as employees')
        )
        ->groupBy(
            'clients.client_name',
            'clients.email',
            'clients.address'
        );

    $rows = $query->get();

    return Excel::download(
        new ClientsReportExport($rows, $columns),
        'clients-report.xlsx'
    );
}

  public function index(Request $request)
    {
        $perPage = $request->get('per_page', 2);
        $search  = $request->get('search');

        $clients = Client::query()
            ->leftJoin('cases', 'clients.id', '=', 'cases.client_id')
            ->leftJoin('priorities', 'cases.priority_id', '=', 'priorities.id')
            ->leftJoin('case_employees', 'cases.id', '=', 'case_employees.case_id')

            /* ===== SEARCH ===== */
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('clients.client_name', 'LIKE', "%{$search}%")
                        ->orWhere('clients.email', 'LIKE', "%{$search}%")
                        ->orWhere('clients.address', 'LIKE', "%{$search}%");
                });
            })

            ->select(
                'clients.id as client_id',
                'clients.client_name',
                'clients.email',
                'clients.address',

                DB::raw('COUNT(DISTINCT cases.id) as total_cases'),
                DB::raw('COUNT(DISTINCT case_employees.employee_id) as employees_count')
            )

            ->groupBy(
                'clients.id',
                'clients.client_name',
                'clients.email',
                'clients.address'
            )

            ->orderBy('clients.client_name')
            ->paginate($perPage);

        return response()->json($clients);
    }

}
