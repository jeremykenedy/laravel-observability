<?php

use Illuminate\Support\Facades\Route;
use Jeremykenedy\LaravelObservability\Http\Controllers\AssetController;

Route::get('/health/assets/{asset}', AssetController::class)->name('health.assets');
