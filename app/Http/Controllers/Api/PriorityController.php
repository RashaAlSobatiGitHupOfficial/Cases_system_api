<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Priority;
use Illuminate\Http\Request;

class PriorityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $priorities = Priority::query()
            ->when($search, function ($query) use ($search) {
                $query->where('priority_name', 'LIKE', "%{$search}%");
            })
            ->get();

        return response()->json($priorities);
    }

    public function store(Request $request)
    {
       $validated = $request->validate([
        'priority_name'   => 'required|string|max:255',
        'delay_time' => 'required|integer',
    ]);

   
    $priority = Priority::create([
        'priority_name' => $validated['priority_name'],
        'delay_time' => $validated['delay_time'],
    ]);


    return response()->json([
        'message' => 'Priority created successfully',
        'Priority' => $priority
    ], 201);
    }

    public function show(Priority $priority)
    {
        return response()->json(['priority' => $priority]);
    }

  

    public function update(Request $request, Priority $priority)
    {
        $request->validate([
        'priority_name'   => 'nullable|string|max:255',
        'delay_time' => 'nullable|integer',
    ]);

      
       $priority->update($request->all());

        return response()->json([
            'message' => 'priority updated successfully',
            'priority' => $priority
        ]);
    }

    public function destroy(Priority $priority)
    {
        $priority->delete();
        return response()->json(['message' => 'priority deleted successfully']);
    }

}
