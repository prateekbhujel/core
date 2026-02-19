<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UiOptionsController;

// ── Public routes ─────────────────────────────────────────
Route::get('/',       fn() => redirect('/login'));
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::get('/auth/facebook/redirect', [AuthController::class, 'facebookRedirect'])->name('facebook.redirect');
Route::get('/auth/facebook/callback', [AuthController::class, 'facebookCallback'])->name('facebook.callback');
Route::get('/2fa', [AuthController::class, 'showTwoFactor'])->name('2fa.form');
Route::post('/2fa/verify', [AuthController::class, 'verifyTwoFactor'])->name('2fa.verify');
Route::post('/2fa/resend', [AuthController::class, 'resendTwoFactor'])->name('2fa.resend');

// ── Protected routes ──────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/docs', fn() => view('docs.starter-kit'))->name('docs.index');
    Route::get('/docs/starter-kit', fn() => redirect()->route('docs.index'))->name('docs.starter');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/security', [SettingsController::class, 'updateMySecurity'])->name('settings.security');
    Route::post('/settings/users/{user}/access', [SettingsController::class, 'updateUserAccess'])->name('settings.users.access');
    Route::get('/ui/options/leads', [UiOptionsController::class, 'leads'])->name('ui.options.leads');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/broadcast', [NotificationController::class, 'broadcast'])->name('notifications.broadcast');
    Route::post('/logout',   [AuthController::class, 'logout'])->name('logout');
});
