<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CaseController extends Controller
{   public function index(Request $request)
{
    $query = CaseModel::with(['client', 'employees']);

    // Search (title / description)
    if ($request->search) {
        $query->where(function($q) use ($request) {
            $q->where('title', 'LIKE', "%{$request->search}%")
              ->orWhere('description', 'LIKE', "%{$request->search}%");
        });
    }

    // Status filter
    if ($request->status) {
        $query->where('status', $request->status);
    }

    // Type filter
    if ($request->type) {
        $query->where('type', $request->type);
    }

    // Customer filter
    if ($request->client_id) {
        $query->where('client_id', $request->client_id);
    }

    // Date Range
    if ($request->date_from) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }

    if ($request->date_to) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }

    return $query->orderBy('created_at', 'desc')->paginate(10);
}



    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|file',
            'note' => 'nullable|string',
            'type' => 'nullable|in:technical,service_request,delay,miscommunication,enquery,others',
            'way_entry' => 'nullable|in:email,manual',
            'status' => 'nullable|in:opened,assigned,in_progress,reassigned,closed',
            'priority' => 'nullable|in:high,middle,low,normal',
        ]);

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('cases', 'public');
        }

        // الأساسيات اللي لازم دايمًا تنحفظ
        $data = [
            'client_id'   => $request->client_id,
            'title'       => $request->title,
            'description' => $request->description,
            'attachment'  => $attachmentPath,
            'note'        => $request->note,
        ];

        // الحقول الاختيارية: لو ما انرسلت، ما نضيفها → الـ DB يستخدم default
        foreach (['type', 'way_entry', 'status', 'priority'] as $field) {
            if ($request->filled($field)) { // filled = مو null ومو ""
                $data[$field] = $request->$field;
            }
        }

        $case = CaseModel::create($data);

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
        $request->validate([
            'client_id'    => 'sometimes|exists:clients,id',
            'title'        => 'sometimes|string|max:255',
            'description'  => 'sometimes|string',
            'attachment'   => 'nullable|file|max:5120',
            'note'         => 'nullable|string',
            'type'         => 'sometimes|in:technical,service_request,delay,miscommunication,enquery,others',
            'way_entry'    => 'sometimes|in:email,manual',
            'status'       => 'sometimes|in:opened,assigned,in_progress,reassigned,closed',
            'priority'     => 'sometimes|in:high,middle,low,normal',
        ]);

        // Handle attachment update
        if ($request->hasFile('attachment')) {
            if ($case->attachment && Storage::disk('public')->exists($case->attachment)) {
                Storage::disk('public')->delete($case->attachment);
            }
            $case->attachment = $request->file('attachment')->store('cases', 'public');
        }

        $case->update($request->except('attachment'));

        return response()->json([
            'message' => 'Case updated successfully',
            'case'    => $case
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
