<?php

use App\Http\Controllers\SettingsAuthController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/settings');
Route::get('/settings/login', [SettingsAuthController::class, 'form'])->name('settings.login');
Route::post('/settings/login', [SettingsAuthController::class, 'login'])->middleware('throttle:6,1');
Route::middleware('settings.auth')->prefix('settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/profiles', [SettingsController::class, 'store'])->name('settings.profiles.store');
    Route::put('/profiles/{profile}', [SettingsController::class, 'update'])->name('settings.profiles.update');
    Route::delete('/profiles/{profile}', [SettingsController::class, 'destroy'])->name('settings.profiles.destroy');
    Route::post('/logout', [SettingsAuthController::class, 'logout'])->name('settings.logout');
});
