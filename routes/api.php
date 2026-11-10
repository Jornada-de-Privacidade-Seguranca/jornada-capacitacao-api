<?php

use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\CertificationOptionController;
use App\Http\Controllers\Api\CsrfTokenController;
use App\Http\Controllers\Api\CurrentJourneyCompetencyController;
use App\Http\Controllers\Api\CurrentJourneyCoursesController;
use App\Http\Controllers\Api\CurrentJourneyGeneralDataController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JourneyAnswersController;
use App\Http\Controllers\Api\JourneyCancellationController;
use App\Http\Controllers\Api\JourneyCertificationController;
use App\Http\Controllers\Api\JourneyCertificationsController;
use App\Http\Controllers\Api\JourneyCompetenciesController;
use App\Http\Controllers\Api\JourneyCourseOptionsController;
use App\Http\Controllers\Api\JourneyCoursesController;
use App\Http\Controllers\Api\JourneyFinalizationController;
use App\Http\Controllers\Api\JourneyProfileController;
use App\Http\Controllers\Api\JourneyProfileSelectionController;
use App\Http\Controllers\Api\JourneyResultPdfController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\TemplateExampleController;
use App\Http\Controllers\Api\UserAccountAdministrationController;
use App\Http\Controllers\Api\UserInvitationController;
use App\Http\Controllers\Api\UserJourneyController;
use App\Http\Controllers\Api\UserRegistrationController;
use App\Http\Middleware\AuthenticateSessionUser;
use App\Http\Middleware\ValidateSessionCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', UserRegistrationController::class)->middleware('throttle:registration');
Route::post('/auth/invitations/validate', [UserInvitationController::class, 'validate'])
    ->middleware('throttle:invitation-token');
Route::post('/auth/invitations/complete', [UserInvitationController::class, 'complete'])
    ->middleware('throttle:invitation-completion');

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
        Route::get('/admin/users/pending-activation', [UserAccountAdministrationController::class, 'pending'])
            ->middleware('permission:users.view');
        Route::post('/admin/users/{user}/activate', [UserAccountAdministrationController::class, 'activate'])
            ->middleware('permission:users.deactivate');
        Route::post('/admin/users/{user}/deactivate', [UserAccountAdministrationController::class, 'deactivate'])
            ->middleware('permission:users.deactivate');
        Route::post('/admin/user-invitations', [UserInvitationController::class, 'store'])
            ->middleware('permission:users.register')
            ->middleware('throttle:user-invitation');
        Route::put('/me/journey-profile', [JourneyProfileSelectionController::class, 'update']);
        Route::get('/dashboard', DashboardController::class);
        Route::apiResource('journey-profiles', JourneyProfileController::class)
            ->only(['index', 'show']);
        Route::get('/users/{user}/journeys', [UserJourneyController::class, 'index']);
        Route::get('/journeys/current/certifications/options', CertificationOptionController::class)
            ->middleware('permission:journeys.take');
        Route::get('/journeys/current/competencies/options', [CurrentJourneyCompetencyController::class, 'options'])
            ->middleware('permission:journeys.take');
        Route::get('/journeys/current/competencies', [CurrentJourneyCompetencyController::class, 'show'])
            ->middleware('permission:journeys.take');
        Route::put('/journeys/current/competencies', [CurrentJourneyCompetencyController::class, 'update'])
            ->middleware('permission:journeys.take');
        Route::get('/journeys/current/certifications', [JourneyCertificationController::class, 'show'])
            ->middleware('permission:journeys.take');
        Route::put('/journeys/current/certifications', [JourneyCertificationController::class, 'update'])
            ->middleware('permission:journeys.take');
        Route::get('/journeys/current/courses/options', JourneyCourseOptionsController::class)
            ->middleware('permission:journeys.take');
        Route::get('/journeys/current/courses', [CurrentJourneyCoursesController::class, 'index'])
            ->middleware('permission:journeys.take');
        Route::put('/journeys/current/courses', [CurrentJourneyCoursesController::class, 'update'])
            ->middleware('permission:journeys.take');
        Route::get('/journeys/current/general-data/options', [CurrentJourneyGeneralDataController::class, 'options'])
            ->middleware('permission:journeys.take');
        Route::put('/journeys/current/general-data', [CurrentJourneyGeneralDataController::class, 'update'])
            ->middleware('permission:journeys.take');
        Route::get('/journeys/{journey}', [UserJourneyController::class, 'show']);
        Route::get('/journeys/{journey}/pdf', JourneyResultPdfController::class);
        Route::post('/users/{user}/journeys', [UserJourneyController::class, 'store']);
        Route::post('/journeys/{journey}/cancel', JourneyCancellationController::class);
        Route::patch('/journeys/{journey}/answers', JourneyAnswersController::class)
            ->middleware('permission:journeys.take');
        Route::post('/journeys/{journey}/courses', JourneyCoursesController::class)
            ->middleware('permission:journeys.take');
        Route::post('/journeys/{journey}/certifications', JourneyCertificationsController::class)
            ->middleware('permission:journeys.take');
        Route::post('/journeys/{journey}/competencies', JourneyCompetenciesController::class)
            ->middleware('permission:journeys.take');
        Route::post('/journeys/current/finalize', JourneyFinalizationController::class)
            ->middleware('permission:journeys.take');
        Route::post('/auth/logout', LogoutController::class);
    });
});
