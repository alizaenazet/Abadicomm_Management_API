<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\JobdescController;
use App\Http\Controllers\ScheduleController;

// Authentication Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// API Routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/workers', [WorkerController::class, 'index']);
    Route::get('/workers/karyawan', [WorkerController::class, 'getKaryawan']);
    Route::get('/workers/supervisors', [WorkerController::class, 'getSupervisors']);
    Route::post('/workers', [WorkerController::class, 'store']);

    Route::get('/roles', [RoleController::class, 'index']);

    Route::get('/jobdescs', [JobdescController::class, 'index']);
    Route::post('/jobdescs', [JobdescController::class, 'store']);

    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::post('/schedules', [ScheduleController::class, 'store']);
    Route::post('/schedules/bulk', [ScheduleController::class, 'bulkStore']);
    Route::put('/schedules/bulk-update', [ScheduleController::class, 'bulkUpdate']);
    Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy']);
});
