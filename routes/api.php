<?php

use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\CompetencyController;
use App\Http\Controllers\Api\CsrfTokenController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\TemplateExampleController;
use App\Http\Controllers\Api\UserRegistrationController;
use App\Http\Middleware\AuthenticateSessionUser;
use App\Http\Middleware\ValidateSessionCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', UserRegistrationController::class)->middleware('throttle:registration');

// Rotas temporarias de teste/scaffold do projeto.
// Quando os endpoints reais forem implementados, remova estas rotas de exemplo
// antes de criar rotas definitivas com os mesmos nomes ou responsabilidades.
Route::get('/health', HealthController::class);
Route::get('/template-example', TemplateExampleController::class);

// Rota CRUD temporaria usada apenas para validar o padrao MVC + Services + Repositories.
// Nao usar como endpoint real de competencias; excluir este scaffold quando as
// rotas oficiais de competencias forem criadas.
Route::apiResource('competencies', CompetencyController::class);

// Rota de autenticacao de usuario
Route::middleware([StartSession::class, ValidateSessionCsrfToken::class])->group(function (): void {
    Route::get('/auth/csrf-token', CsrfTokenController::class);
    Route::post('/auth/login', LoginController::class)->middleware('throttle:login');
    Route::get('/me', AuthenticatedUserController::class)->middleware(AuthenticateSessionUser::class);
    Route::get('/dashboard', DashboardController::class)->middleware(AuthenticateSessionUser::class);
    Route::post('/auth/logout', LogoutController::class)->middleware(AuthenticateSessionUser::class);
});
