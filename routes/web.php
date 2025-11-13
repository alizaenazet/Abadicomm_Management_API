<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleViewController;
use App\Http\Controllers\AssignWorkerController;

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
    return view('welcome');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Schedule Management
    Route::get('/schedule', [ScheduleViewController::class, 'showSchedulePage'])->name('schedule.page');
    Route::get('/schedule/edit/{dateKey}/{supervisor}/{start}', [ScheduleViewController::class, 'edit'])->name('schedule.edit');
    Route::post('/schedule/update', [ScheduleViewController::class, 'update'])->name('schedule.update');

    Route::get('/assign', [AssignWorkerController::class, 'index'])->name('assign');
    Route::post('/assign', [AssignWorkerController::class, 'store'])->name('assign.store');
});
