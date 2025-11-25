<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;

class PermissionController extends Controller
{
   public function index(Request $request)
{
    $search = $request->input('search');

    $permissions = Permission::when($search, function ($query, $search) {
            $query->where('permission_name', 'LIKE', "%{$search}%");
        })
        ->orderBy('id', 'DESC')
        ->paginate(5);

    return response()->json($permissions);
}


public function loadAll()  {

    return response()->json([
    'permissions' => Permission::all()
]);
    
}
    public function store(Request $request)
    {
        $request->validate([
            'permission_name' => 'required|string|unique:permissions,permission_name|max:50',
        ]);

        $permission = Permission::create(['permission_name' => $request->permission_name]);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission
        ], 201);
    }

    public function show(Permission $permission)
    {
        return response()->json(['permission' => $permission]);
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'permission_name' => 'required|string|unique:permissions,permission_name,' . $permission->id . '|max:50',
        ]);

        $permission->update(['permission_name' => $request->permission_name]);

        return response()->json([
            'message' => 'Permission updated successfully',
            'permission' => $permission
        ]);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
