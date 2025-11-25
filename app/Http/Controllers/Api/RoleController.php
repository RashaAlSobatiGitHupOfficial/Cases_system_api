<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    
    public function index(Request $request)
{
     $search = $request->query('search');

    $roles = Role::with('permissions')
        ->when($search, function ($query) use ($search) {
            $query->where('role_name', 'LIKE', "%{$search}%");
        })
        ->orderBy('id', 'DESC')
        ->paginate(5);

    return response()->json($roles);
}

    public function store(Request $request)
    {
       $validated = $request->validate([
        'role_name'   => 'required|string|max:255',
        'permissions' => 'required|array',
        'permissions.*' => 'exists:permissions,id'
    ]);

   
    $role = Role::create([
        'role_name' => $validated['role_name']
    ]);

    
    $role->permissions()->attach($validated['permissions']);

    return response()->json([
        'message' => 'Role created successfully',
        'role' => $role->load('permissions')
    ], 201);
    }

    public function show(Role $role)
    {
        return response()->json(['role' => $role]);
    }

  

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'role_name'   => 'nullable|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if (isset($validated['role_name']) && $validated['role_name'] !== '') {
            $role->role_name = $validated['role_name'];
        }

        $role->save();

        // Sync pivot table
        $role->permissions()->sync($validated['permissions']);

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role->load('permissions')
        ]);
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'Role deleted successfully']);
    }
}
