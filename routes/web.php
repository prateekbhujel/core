<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// ── Public routes ─────────────────────────────────────────
Route::get('/',       fn() => redirect('/login'));
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// ── Protected routes ──────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/docs/starter-kit', fn() => view('docs.starter-kit'))->name('docs.starter');
    Route::post('/logout',   [AuthController::class, 'logout'])->name('logout');
});
