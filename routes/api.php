<?php

use App\Http\Controllers\Api\CompetencyController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::apiResource('competencies', CompetencyController::class);
