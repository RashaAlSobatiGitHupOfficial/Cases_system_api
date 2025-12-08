<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PermissionCategory;
use App\Models\Permission;

class PermissionCategoryController extends Controller
{
    public function loadAll()
    {
        return response()->json([
            'categories' => PermissionCategory::with('permissions')->get()
        ]);
    }

    public function index(Request $request)
    {
         $search = $request->query('search');

    $data = PermissionCategory::with('permissions')
        ->when($search, function ($query) use ($search) {
            $query->where('category_name', 'LIKE', "%{$search}%");
        })
        ->orderBy('id', 'DESC')
        ->paginate(5);

    return response()->json($data);

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'required|string|max:255',
        ]);

        $category = PermissionCategory::create([
            'category_name' => $validated['category_name']
        ]);

        if (!empty($validated['permissions'])) {
            foreach ($validated['permissions'] as $permissionName) {
                Permission::create([
                    'permission_name' => $permissionName,
                    'category_id'     => $category->id,
                ]);
            }
        }

        return response()->json([
            'message'   => 'Category created successfully',
            'category'  => $category->load('permissions')
        ], 201);
    }

    public function show($id)
    {
        $category = PermissionCategory::with('permissions')->findOrFail($id);

        return response()->json([
            'category' => $category
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = PermissionCategory::findOrFail($id);

        $validated = $request->validate([
            'category_name' => 'sometimes|string|max:255',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|max:255'
        ]);

        // Update category name
        if (isset($validated['category_name'])) {
            $category->update([
                'category_name' => $validated['category_name']
            ]);
        }

        // If permissions were sent, sync them intelligently
        if (array_key_exists('permissions', $validated)) {

            $incoming = collect($validated['permissions'])->map(fn($p) => trim($p))->filter()->unique();

            // Existing permissions in DB for this category
            $existing = Permission::where('category_id', $category->id)
                        ->pluck('permission_name');

            // 1) Add new permissions (incoming but not existing)
            $toAdd = $incoming->diff($existing);
            foreach ($toAdd as $name) {
                Permission::create([
                    'permission_name' => $name,
                    'category_id'     => $category->id
                ]);
            }

            // 2) Delete removed permissions (existing but not incoming)
            $toDelete = $existing->diff($incoming);
            Permission::where('category_id', $category->id)
                ->whereIn('permission_name', $toDelete)
                ->delete();
        }

        return response()->json([
            'message'  => 'Category updated successfully',
            'category' => $category->load('permissions')
        ]);
    }


    public function destroy($id)
    {
        $category = PermissionCategory::findOrFail($id);

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}

