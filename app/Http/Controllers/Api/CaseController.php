<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseEmployee;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\CaseLog;

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
            // TAB FILTERS
        if ($request->tab === 'mine') {
            $emp = $request->user()->employee->id;

            $query->whereHas('employees', function($q) use ($emp) {
                $q->where('employee_id', $emp);
            });
        }

        if ($request->tab === 'unassigned') {
            $query->whereDoesntHave('employees');
        }



        if ($request->filled('sort_by')) {
            $direction = $request->sort_direction === 'desc' ? 'desc' : 'asc';
            $query->orderBy($request->sort_by, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        if ($request->filled('employee_ids') && is_array($request->employee_ids)) {
            $query->whereHas('employees', function($q) use ($request) {
                $q->whereIn('employee_id', $request->employee_ids);
            });
        }


        return $query->paginate(10)->through(function($case) {
            return $case->load(['client', 'priority', 'employees']);
        });
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

        $data['type'] = $request->type ?? 'enquery';
        $data['way_entry'] = $request->way_entry ?? 'email';
        $data['status'] = $request->filled('employee_ids') ? 'assigned' : 'opened';

        if ($request->filled('employee_ids') && count($request->employee_ids) > 0) {
                $data['status'] = 'assigned';
            } else {
                $data['status'] = 'opened';
            }


        $case = CaseModel::create($data);

    //   $primaryId = $request->primary_employee_id ?? $request->employee_ids[0];

        if ($request->filled('employee_ids')) {
                $isFirst = true;

            foreach ($request->employee_ids as $empId) {
                CaseEmployee::create([
                    'case_id'     => $case->id,
                    'employee_id' => $empId,
                    'action'      => 'assigned',
                    'assigned_by' => $request->user()->id,
                    'is_primary' => $isFirst ? 1 : 0,
                    'started_at'  => now(),
                    'ended_at'    => null



                ]);
                        $isFirst = false;

            }
        }
        CaseLog::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'action'  => 'created',
            'new_value' => $case->toArray(),
        ]);

        return response()->json([
            'message' => 'Case created successfully',
            'case'    => $case
        ], 201);
    }



public function show(CaseModel $case)
{
    // Load all related data including logs
    $case->load([
        'client',
        'priority',
        'employees',
        'allEmployees',
        'logs'
    ]);

   foreach ($case->employees as $employee) {

    $userId = $employee->user_id;

    $accept = $case->logs
        ->where('action', 'case_accepted')
        ->where('user_id', $userId)
        ->first();

    $closed = $case->logs
        ->where('action', 'closed')
        ->where('user_id', $userId)
        ->first();

    if ($closed) {
        $employee->case_status = 'closed';
    } elseif ($accept) {
        $employee->case_status = 'accepted';
    } else {
        $employee->case_status = 'pending';
    }

    $employee->accepted_at = $accept?->created_at;
    $employee->closed_at   = $closed?->created_at;
    $employee->timeline = $case->logs
    ->whereIn('action', ['case_accepted', 'closed'])
    ->where('user_id', $employee->user_id)
    ->sortBy('created_at')
    ->values()   // important for Vue
    ->map(function($log) {
        return [
            'action' => $log->action,
            'time'   => $log->created_at
        ];
    });

}


    return response()->json(['case' => $case]);
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

    // Handle attachment
    if ($request->hasFile('attachment')) {
        if ($case->attachment && Storage::disk('public')->exists($case->attachment)) {
            Storage::disk('public')->delete($case->attachment);
        }

        $path = $request->file('attachment')->store('cases', 'public');
        $case->attachment = $path;
    }

    $case->save();


    // Return with relationships
    $case->load(['client', 'employees', 'priority']);
        CaseLog::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'action'  => 'updated',
            'new_value' => json_encode($case->toArray())
        ]);
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
        // $case->employees()->sync($validated['employee_ids']);
        foreach ($validated['employee_ids'] as $empId) {
            CaseEmployee::updateOrCreate(
                ['case_id' => $case->id, 'employee_id' => $empId],
                [
                    'action'      => 'assigned',
                    'assigned_by' => $request->user()->id,
                    'started_at'  => now(),
                    'ended_at'    => null,
                    'is_primary'  => false
                ]
            );
        }

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
    public function adminUpdateEmployees(Request $request, CaseModel $case)
{
    $this->authorize('editEmployees', $case);

    $validated = $request->validate([
        'employee_ids' => 'required|array',
        'employee_ids.*' => 'exists:employees,id',
    ]);

    // Sync employees
    $case->allEmployees()->sync($validated['employee_ids']);

    return response()->json([
        'message' => 'Employees updated successfully.',
        'case' => $case->fresh()->load(['employees'])
    ]);
}


}
