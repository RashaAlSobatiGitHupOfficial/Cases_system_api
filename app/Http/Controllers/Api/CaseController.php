<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseEmployee;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CaseController extends Controller

{  
    public function index(Request $request)
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

    return $query->paginate(10);
}




  public function store(Request $request)
    {
        $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'priority_id' => 'nullable|exists:priorities,id',
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'attachment'  => 'nullable|file',
            'note'        => 'nullable|string',
            'type'        => 'nullable|in:technical,service_request,delay,miscommunication,enquery,others',
            'way_entry'   => 'nullable|in:email,manual',
            'status'      => 'nullable|in:opened,assigned,in_progress,reassigned,closed',

            
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('cases', 'public');
        }

        $user = $request->user();

        $data = [
            'client_id'   => $request->client_id,
            'priority_id' => $request->priority_id,
            'user_id'     => $user->id,
            'title'       => $request->title,
            'description' => $request->description,
            'attachment'  => $attachmentPath,
            'note'        => $request->note,
        ];

        foreach (['type', 'way_entry', 'status'] as $field) {
            if ($request->filled($field)) {
                $data[$field] = $request->$field;
            }
        }

        $case = CaseModel::create($data);

      
        if ($request->filled('employee_ids')) {
            foreach ($request->employee_ids as $empId) {
                CaseEmployee::create([
                    'case_id'     => $case->id,
                    'employee_id' => $empId,
                    'status'      => 'assigned'
                ]);
            }
        }

        return response()->json([
            'message' => 'Case created successfully',
            'case'    => $case
        ], 201);
    }



    public function show(CaseModel $case)
    {
        return response()->json(['case' => $case->load('client')]);
    }

public function update(Request $request, CaseModel $case)
{
    $validated = $request->validate([
        'client_id'     => 'required|exists:clients,id',
        'priority_id'   => 'required|exists:priorities,id',
        'title'         => 'required|string|max:255',
        'description'   => 'required|string',
        'note'          => 'nullable|string',
        'type'          => 'required|in:technical,service_request,delay,miscommunication,enquery,others',
        'way_entry'     => 'nullable|in:email,manual',
        'status'        => 'nullable|in:opened,assigned,in_progress,reassigned,closed',

        'employee_ids'  => 'array',
        'employee_ids.*'=> 'exists:employees,id',

        'attachment'    => 'nullable|file|max:5120',
    ]);

    // Update main fields
    $case->client_id    = $validated['client_id'];
    $case->priority_id  = $validated['priority_id'];
    $case->title        = $validated['title'];
    $case->description  = $validated['description'];
    $case->note         = $validated['note'] ?? $case->note;
    $case->type         = $validated['type'];
    $case->way_entry    = $validated['way_entry'] ?? $case->way_entry;
    $case->status       = $validated['status'] ?? $case->status;

    // Handle attachment
    if ($request->hasFile('attachment')) {
        if ($case->attachment && Storage::disk('public')->exists($case->attachment)) {
            Storage::disk('public')->delete($case->attachment);
        }

        $path = $request->file('attachment')->store('cases', 'public');
        $case->attachment = $path;
    }

    $case->save();

    // Sync employees
    if ($request->has('employee_ids')) {
        $case->employees()->sync($validated['employee_ids']);
    }

    // Return with relationships
    $case->load(['client', 'employees', 'priority']);

    return response()->json([
        'message' => 'Case updated successfully',
        'data'    => $case
    ]);
}

    public function assignEmployees(Request $request, CaseModel $case)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        // Assign (sync ensures clean pivot)
        $case->employees()->sync($validated['employee_ids']);

        // Auto-change status
        if ($case->status === 'opened') {
            $case->update(['status' => 'assigned']);
        }

        return response()->json([
            'message' => 'Employees assigned successfully',
            'case' => $case->load('employees'),
        ]);
    }


    public function destroy(CaseModel $case)
    {
        if ($case->attachment && Storage::disk('public')->exists($case->attachment)) {
            Storage::disk('public')->delete($case->attachment);
        }

        $case->delete();

        return response()->json(['message' => 'Case deleted successfully']);
    }
}
