<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleViewController;
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
use App\Http\Controllers\AssignWorkerController;
use App\Http\Controllers\JadwalController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/schedule', [ScheduleViewController::class, 'showSchedulePage'])->name('schedule.page');
    Route::get('/assign', [AssignWorkerController::class, 'index'])->name('assign');
    Route::post('/assign', [AssignWorkerController::class, 'store'])->name('assign.store');
    Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal');
    Route::get('/jadwal/edit/{dateKey}/{supervisor}/{start}', [JadwalController::class, 'edit'])->name('jadwal.edit');
    Route::post('/jadwal/update', [JadwalController::class, 'update'])->name('jadwal.update');
});
