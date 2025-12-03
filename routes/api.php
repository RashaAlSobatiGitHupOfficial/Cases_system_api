<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\CaseEmployeeController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PermissionCategoryController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PriorityController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('employees', EmployeeController::class);
    Route::get('roles/paginations', [RoleController::class,'indexPagination']);
    Route::apiResource('roles', RoleController::class);
    // Route::get('permissions/all_permissions', [PermissionController::class,'loadAll']);
    // Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('cases', CaseController::class);

    Route::apiResource('users', UserController::class);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
      
    // Route::get('/case-employees', [CaseEmployeeController::class, 'index'])->name('caseEmployees.index');
    // Route::post('/case-employees', [CaseEmployeeController::class, 'store'])->name('caseEmployees.store');

    Route::get('/permission-categories/loadAll', [PermissionCategoryController::class, 'loadAll']);
    Route::apiResource('permission-categories', PermissionCategoryController::class);
    Route::post('/permissions', [PermissionController::class, 'store']);


    Route::apiResource('priorities', PriorityController::class);
    Route::put('/account/update', [UserController::class, 'updateAccount']);

    Route::post('/cases/{case}/assign', [CaseController::class, 'assignEmployees']);

    Route::prefix('dashboard')->group(function () {

    Route::get('/cards', [DashboardController::class, 'cards']);
    Route::get('/cases-per-day', [DashboardController::class, 'casesPerDay']);
    Route::get('/cases-by-status', [DashboardController::class, 'casesByStatus']);
    Route::get('/cases-by-priority', [DashboardController::class, 'casesByPriority']);
    Route::get('/cases-by-type', [DashboardController::class, 'casesByType']);
    Route::get('/top-clients', [DashboardController::class, 'topClients']);
    Route::get('/completion-rate', [DashboardController::class, 'completionRate']);

});

});
