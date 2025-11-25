<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
public function index(Request $request)
{
    $query = Employee::with(['user.role']);

    // Search filter
    if ($request->search) {
        $query->where(function ($q) use ($request) {
            $q->where('first_name', 'like', '%'.$request->search.'%')
              ->orWhere('middle_name', 'like', '%'.$request->search.'%')
              ->orWhere('last_name', 'like', '%'.$request->search.'%')
              ->orWhere('email', 'like', '%'.$request->search.'%')
              ->orWhere('phone', 'like', '%'.$request->search.'%');
        });
    }

    // Role filter
    if ($request->role) {
        $query->whereHas('user', function ($q) use ($request) {
            $q->where('role_id', $request->role);
        });
    }

    return $query->paginate(10);
}


    public function store(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|unique:users,username',
            'password'  => 'required|string|confirmed|min:8',
            'role_id'   => 'required|exists:roles,id',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50',
            'email'     => 'required|email|unique:employees,email',
            'gender'    => 'required|in:male,female',
            'phone'     => 'required|string|unique:employees,phone',
        ]);

        // Create user
        $user = User::create([
            'username' => $request->username,
            'password' => hash::make($request->password),
            'role_id'  => $request->role_id
        ]);

        // Create employee linked to user
        $user->employee()->create([
            'first_name'  => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'   => $request->last_name,
            'email'       => $request->email,
            'gender'      => $request->gender,
            'phone'       => $request->phone,
        ]);



        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user->load('employee'),
        ], 201);
    }

    public function show(Employee $employee)
    {
        return response()->json($employee->load('user'));
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'first_name' => 'sometimes|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name'  => 'sometimes|string|max:50',
            'email'      => 'sometimes|email|unique:employees,email,' . $employee->id,
            'gender'     => 'sometimes|in:male,female',
            'phone'      => 'sometimes|string|unique:employees,phone,' . $employee->id,
        ]);

        $employee->update($request->all());

        return response()->json([
            'message' => 'Employee updated',
            'employee' => $employee
        ]);
    }
    

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return response()->json(['message' => 'Employee deleted']);
    }
}
