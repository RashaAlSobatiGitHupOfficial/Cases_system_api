<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\CaseEmployeeController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('roles', RoleController::class);
    Route::get('permissions/all_permissions', [PermissionController::class,'loadAll']);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('cases', CaseController::class);

    Route::apiResource('users', UserController::class);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
      
    Route::get('/case-employees', [CaseEmployeeController::class, 'index'])->name('caseEmployees.index');
    Route::post('/case-employees', [CaseEmployeeController::class, 'store'])->name('caseEmployees.store');
});
