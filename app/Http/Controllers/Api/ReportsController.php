<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use App\Models\CaseEmployee;

class ReportsController extends Controller
{
    public function casesReport(Request $request)
    {
        $query = CaseModel::with(['client', 'employees', 'priority']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('way_entry')) {
            $query->where('way_entry', $request->way_entry);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('priority')) {
            $query->whereHas('priority', function ($q) use ($request) {
                $q->where('priority_name', 'LIKE', '%' . trim($request->priority) . '%');
            });
        }

        if ($request->filled('sort_by')) {
            $direction = $request->sort_direction === 'desc' ? 'desc' : 'asc';
            $query->orderBy($request->sort_by, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate(10)->through(function($case) {
            return $case->load(['client', 'priority', 'employees']);
        });
    }

    /* ============================================================
     *                  EXPORT (OPTIONAL IF YOU WANT)
     * ============================================================ */
    public function exportCases(Request $request)
    {
        // You can add CSV export here later.
        return response()->json(['message' => 'CSV export not implemented'], 200);
    }
}
