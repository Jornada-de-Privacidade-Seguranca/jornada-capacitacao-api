<?php

namespace App\Providers;

use App\Repositories\Contracts\CompetencyRepositoryInterface;
use App\Repositories\Contracts\TemplateExampleRepositoryInterface;
use App\Repositories\Eloquent\CompetencyRepository;
use App\Repositories\Eloquent\TemplateExampleRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompetencyRepositoryInterface::class, CompetencyRepository::class);
        $this->app->bind(TemplateExampleRepositoryInterface::class, TemplateExampleRepository::class);
    }
}
