<?php

namespace App\Providers;

use App\Repositories\Contracts\CompetencyRepositoryInterface;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Contracts\TemplateExampleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UsuarioRepositoryInterface;
use App\Repositories\Eloquent\CompetencyRepository;
use App\Repositories\Eloquent\JourneyRepository;
use App\Repositories\Eloquent\TemplateExampleRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\UsuarioRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompetencyRepositoryInterface::class, CompetencyRepository::class);
        $this->app->bind(JourneyRepositoryInterface::class, JourneyRepository::class);
        $this->app->bind(TemplateExampleRepositoryInterface::class, TemplateExampleRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UsuarioRepositoryInterface::class, UsuarioRepository::class);
    }
}
