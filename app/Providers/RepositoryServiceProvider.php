<?php

namespace App\Providers;

use App\Repositories\Contracts\CompetencyCriterionWeightRepositoryInterface;
use App\Repositories\Contracts\CompetencyRepositoryInterface;
use App\Repositories\Contracts\CriterionRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Contracts\ProfessionalCertificationRepositoryInterface;
use App\Repositories\Contracts\SubcriterionRepositoryInterface;
use App\Repositories\Contracts\TemplateExampleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\CompetencyCriterionWeightRepository;
use App\Repositories\Eloquent\CompetencyRepository;
use App\Repositories\Eloquent\CriterionRepository;
use App\Repositories\Eloquent\DashboardRepository;
use App\Repositories\Eloquent\JourneyProfileRepository;
use App\Repositories\Eloquent\JourneyRepository;
use App\Repositories\Eloquent\ProfessionalCertificationRepository;
use App\Repositories\Eloquent\SubcriterionRepository;
use App\Repositories\Eloquent\TemplateExampleRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompetencyCriterionWeightRepositoryInterface::class, CompetencyCriterionWeightRepository::class);
        $this->app->bind(CompetencyRepositoryInterface::class, CompetencyRepository::class);
        $this->app->bind(CriterionRepositoryInterface::class, CriterionRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(JourneyProfileRepositoryInterface::class, JourneyProfileRepository::class);
        $this->app->bind(JourneyRepositoryInterface::class, JourneyRepository::class);
        $this->app->bind(ProfessionalCertificationRepositoryInterface::class, ProfessionalCertificationRepository::class);
        $this->app->bind(SubcriterionRepositoryInterface::class, SubcriterionRepository::class);
        $this->app->bind(TemplateExampleRepositoryInterface::class, TemplateExampleRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}
