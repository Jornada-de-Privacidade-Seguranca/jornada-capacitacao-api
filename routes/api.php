<?php

use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\CsrfTokenController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JourneyCourseOptionsController;
use App\Http\Controllers\Api\JourneyProfileController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\TemplateExampleController;
use App\Http\Controllers\Api\UserJourneyController;
use App\Http\Controllers\Api\UserRegistrationController;
use App\Http\Middleware\AuthenticateSessionUser;
use App\Http\Middleware\ValidateSessionCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', UserRegistrationController::class)->middleware('throttle:registration');

// Rota temporaria de teste/scaffold do projeto.
// Quando o projeto nao precisar mais demonstrar o contrato padrao, remova este exemplo.
Route::get('/template-example', TemplateExampleController::class);

// Rota de autenticacao de usuario
Route::middleware([StartSession::class, ValidateSessionCsrfToken::class])->group(function (): void {
    Route::get('/auth/csrf-token', CsrfTokenController::class);
    Route::post('/auth/login', LoginController::class)->middleware('throttle:login');
    Route::get('/me', AuthenticatedUserController::class)->middleware(AuthenticateSessionUser::class);
    Route::get('/dashboard', DashboardController::class)->middleware(AuthenticateSessionUser::class);
    Route::apiResource('journey-profiles', JourneyProfileController::class)
        ->only(['index', 'show'])
        ->middleware(AuthenticateSessionUser::class);
    Route::get('/users/{user}/journeys', [UserJourneyController::class, 'index'])->middleware(AuthenticateSessionUser::class);
    Route::get('/journeys/current/courses/options', JourneyCourseOptionsController::class)->middleware(AuthenticateSessionUser::class);
    Route::get('/journeys/{journey}', [UserJourneyController::class, 'show'])->middleware(AuthenticateSessionUser::class);
    Route::post('/auth/logout', LogoutController::class)->middleware(AuthenticateSessionUser::class);
});
