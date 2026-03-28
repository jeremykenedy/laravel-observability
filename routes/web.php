<?php

use Illuminate\Support\Facades\Route;
use Jeremykenedy\LaravelObservability\Http\Controllers\HealthController;

Route::get(config('observability.health.route', '/health'), HealthController::class)
    ->middleware(config('observability.health.middleware', []))
    ->name('health');

Route::get('/health/providers', [HealthController::class, 'providers'])
    ->middleware(config('observability.health.middleware', []))
    ->name('health.providers');

Route::get('/health/uptime', [HealthController::class, 'uptime'])
    ->middleware(array_merge(['web', 'auth'], config('observability.health.middleware', [])))
    ->name('health.uptime');

Route::get('/health/dashboard', function () {
    return view('observability::dashboard');
})->middleware(array_merge(['web', 'auth'], config('observability.health.middleware', [])))
    ->name('health.dashboard');
