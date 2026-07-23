<?php

use App\Http\Controllers\Api\CompetencyController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TemplateExampleController;
use App\Http\Controllers\Api\UserRegistrationController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', UserRegistrationController::class);

// Rotas temporarias de teste/scaffold do projeto.
// Quando os endpoints reais forem implementados, remova estas rotas de exemplo
// antes de criar rotas definitivas com os mesmos nomes ou responsabilidades.
Route::get('/health', HealthController::class);
Route::get('/template-example', TemplateExampleController::class);

// Rota CRUD temporaria usada apenas para validar o padrao MVC + Services + Repositories.
// Nao usar como endpoint real de competencias; excluir este scaffold quando as
// rotas oficiais de competencias forem criadas.
Route::apiResource('competencies', CompetencyController::class);
