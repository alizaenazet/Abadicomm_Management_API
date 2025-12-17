<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleViewController;
use App\Http\Controllers\AssignWorkerController;
use App\Http\Controllers\JobdescController;
use App\Http\Controllers\LocationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Change Password
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('change-password');
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Schedule Management
    Route::get('/schedule', [ScheduleViewController::class, 'showSchedulePage'])->name('schedule.page');
    Route::get('/schedule/edit/{dateKey}/{supervisor}/{start}', [ScheduleViewController::class, 'edit'])->name('schedule.edit');
    Route::post('/schedule/update', [ScheduleViewController::class, 'update'])->name('schedule.update');

    // Worker Assignment
    Route::get('/assign', [AssignWorkerController::class, 'index'])->name('assign');
    Route::post('/assign', [AssignWorkerController::class, 'store'])->name('assign.store');

    // Job Description Management
    Route::get('/jobdesc', [JobdescController::class, 'index'])->name('jobdesc.index');
    Route::post('/jobdesc', [JobdescController::class, 'store'])->name('jobdesc.store');
    Route::put('/jobdesc/{id}', [JobdescController::class, 'update'])->name('jobdesc.update');
    Route::delete('/jobdesc/{id}', [JobdescController::class, 'destroy'])->name('jobdesc.destroy');

    // Location Management
    Route::get('/location', [LocationController::class, 'index'])->name('location.index');
    Route::post('/location', [LocationController::class, 'store'])->name('location.store');
    Route::put('/location/{id}', [LocationController::class, 'update'])->name('location.update');
    Route::delete('/location/{id}', [LocationController::class, 'destroy'])->name('location.destroy');
});
