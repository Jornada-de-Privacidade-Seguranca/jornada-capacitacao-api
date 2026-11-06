<?php

use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\CertificationOptionController;
use App\Http\Controllers\Api\CsrfTokenController;
use App\Http\Controllers\Api\CurrentJourneyCompetencyController;
use App\Http\Controllers\Api\CurrentJourneyCoursesController;
use App\Http\Controllers\Api\CurrentJourneyGeneralDataController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JourneyAnswersController;
use App\Http\Controllers\Api\JourneyCertificationController;
use App\Http\Controllers\Api\JourneyCertificationsController;
use App\Http\Controllers\Api\JourneyCompetenciesController;
use App\Http\Controllers\Api\JourneyCourseOptionsController;
use App\Http\Controllers\Api\JourneyCoursesController;
use App\Http\Controllers\Api\JourneyFinalizationController;
use App\Http\Controllers\Api\JourneyProfileController;
use App\Http\Controllers\Api\JourneyProfileSelectionController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\TemplateExampleController;
use App\Http\Controllers\Api\UserAccountAdministrationController;
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

    Route::middleware(AuthenticateSessionUser::class)->group(function (): void {
        Route::get('/me', AuthenticatedUserController::class);
        Route::patch('/me', [AuthenticatedUserController::class, 'update']);
        Route::get('/admin/users/pending-activation', [UserAccountAdministrationController::class, 'pending']);
        Route::post('/admin/users/{user}/activate', [UserAccountAdministrationController::class, 'activate']);
        Route::post('/admin/users/{user}/deactivate', [UserAccountAdministrationController::class, 'deactivate']);
        Route::put('/me/journey-profile', [JourneyProfileSelectionController::class, 'update']);
        Route::get('/dashboard', DashboardController::class);
        Route::apiResource('journey-profiles', JourneyProfileController::class)
            ->only(['index', 'show']);
        Route::get('/users/{user}/journeys', [UserJourneyController::class, 'index']);
        Route::get('/journeys/current/certifications/options', CertificationOptionController::class);
        Route::get('/journeys/current/competencies/options', [CurrentJourneyCompetencyController::class, 'options']);
        Route::get('/journeys/current/competencies', [CurrentJourneyCompetencyController::class, 'show']);
        Route::put('/journeys/current/competencies', [CurrentJourneyCompetencyController::class, 'update']);
        Route::get('/journeys/current/certifications', [JourneyCertificationController::class, 'show']);
        Route::put('/journeys/current/certifications', [JourneyCertificationController::class, 'update']);
        Route::get('/journeys/current/courses/options', JourneyCourseOptionsController::class);
        Route::get('/journeys/current/courses', [CurrentJourneyCoursesController::class, 'index']);
        Route::put('/journeys/current/courses', [CurrentJourneyCoursesController::class, 'update']);
        Route::get('/journeys/current/general-data/options', [CurrentJourneyGeneralDataController::class, 'options']);
        Route::put('/journeys/current/general-data', [CurrentJourneyGeneralDataController::class, 'update']);
        Route::get('/journeys/{journey}', [UserJourneyController::class, 'show']);
        Route::post('/users/{user}/journeys', [UserJourneyController::class, 'store']);
        Route::patch('/journeys/{journey}/answers', JourneyAnswersController::class);
        Route::post('/journeys/{journey}/courses', JourneyCoursesController::class);
        Route::post('/journeys/{journey}/certifications', JourneyCertificationsController::class);
        Route::post('/journeys/{journey}/competencies', JourneyCompetenciesController::class);
        Route::post('/journeys/current/finalize', JourneyFinalizationController::class);
        Route::post('/auth/logout', LogoutController::class);
    });
});
