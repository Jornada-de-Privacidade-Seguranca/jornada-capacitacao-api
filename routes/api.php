<?php

use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\CompetencyController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\TemplateExampleController;
use App\Http\Controllers\Api\UserActivationController;
use App\Http\Controllers\Api\UserRegistrationController;
use App\Http\Middleware\AuthenticateSessionUser;
use App\Http\Middleware\EnsureUserIsAdministrator;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

// Rotas temporarias de teste/scaffold do projeto.
// Quando os endpoints reais forem implementados, remova estas rotas de exemplo
// antes de criar rotas definitivas com os mesmos nomes ou responsabilidades.
Route::get('/health', HealthController::class);
Route::get('/template-example', TemplateExampleController::class);

// Rota CRUD temporaria usada apenas para validar o padrao MVC + Services + Repositories.
// Nao usar como endpoint real de competencias; excluir este scaffold quando as
// rotas oficiais de competencias forem criadas.
Route::apiResource('competencies', CompetencyController::class);

//Rota de registro de usuario
Route::post('/users/register', UserRegistrationController::class);

// Rota temporaria para ativacao manual ate o fluxo administrativo oficial ser definido.
Route::middleware([StartSession::class, AuthenticateSessionUser::class, EnsureUserIsAdministrator::class])
    ->patch('/users/{user}/activate', UserActivationController::class);

// Rota de autenticacao de usuario
Route::middleware([StartSession::class])->group(function (): void {
    Route::post('/auth/login', LoginController::class);
    Route::get('/me', AuthenticatedUserController::class)->middleware(AuthenticateSessionUser::class);
});
