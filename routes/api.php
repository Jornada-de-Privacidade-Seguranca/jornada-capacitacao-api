<?php

use App\Http\Controllers\Api\CompetencyController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TemplateExampleController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/template-example', TemplateExampleController::class);

Route::apiResource('competencies', CompetencyController::class);
