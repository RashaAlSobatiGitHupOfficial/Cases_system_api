<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseEmployee;
use App\Models\CaseModel;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CaseEmployeeController extends Controller
{



    public function index(Request $request)
    {
        $query = CaseEmployee::with(['case', 'employee']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('case_id')) {
            $query->where('case_id', $request->case_id);
        }

        $data = $query->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }



    public function store(Request $request)
    {
        $request->validate([
            'case_id'     => 'required|exists:cases,id',
            'employee_id' => 'required|exists:employees,id',
            'status'      => 'nullable|string'
        ]);

        $record = CaseEmployee::create($request->only('case_id', 'employee_id', 'status'));

        return response()->json([
            'status' => true,
            'message' => 'Employee assigned to case successfully',
            'data' => $record
        ], 201);
    }
}
