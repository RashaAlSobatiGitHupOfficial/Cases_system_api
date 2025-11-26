<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Get all users with pagination + filters.
     */
    public function index(Request $request)
    {
        $query = User::with(['role', 'employee']);

        // Search by username
        if ($request->search) {
            $query->where('username', 'LIKE', "%{$request->search}%");
        }

        // Filter by role
        if ($request->role) {
            $query->where('role_id', $request->role);
        }

        // Filter by status (active / inactive)
        if ($request->status) {
            $query->where('status', $request->status);
        }

        return $query->paginate(10);
    }



    /**
     * Create user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|unique:users,username|max:50',
            'password'  => 'required|string|confirmed|min:8',
            'role_id'   => 'required|exists:roles,id',
            'status'    => 'required|in:active,inactive'
        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
            'status'   => $request->status,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('role', 'employee')
        ], 201);
    }



    /**
     * Show user.
     */
    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load('role', 'employee')
        ]);
    }



    /**
     * Update user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'username'  => 'sometimes|string|max:50|unique:users,username,' . $user->id,
            'role_id'   => 'sometimes|exists:roles,id',
            'status'    => 'sometimes|in:active,inactive'
        ]);

        $user->update($request->only(['username', 'role_id', 'status']));

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('role', 'employee')
        ]);
    }



    /**
     * Reset Password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|confirmed|min:8',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }



    /**
     * Delete user.
     */
    public function destroy(User $user)
    {
        // Delete employee also (optional)
        if ($user->employee) {
            $user->employee->delete();
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }



     public function updateAccount(Request $request)
    {
        $user = $request->user(); 

        $validated = $request->validate([
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'sometimes|email|max:255|unique:employees,email,' . $user->employee->id,
            'password' => 'sometimes|min:6'
        ]);

        if (isset($validated['username'])) {
            $user->username = $validated['username'];
        }

        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (isset($validated['email'])) {
            $employee = $user->employee;
            $employee->email = $validated['email'];
            $employee->save();
        }

        return response()->json([
            'message' => 'Account updated successfully',
            'user'    => $user->load('employee')
        ]);
    }
}
