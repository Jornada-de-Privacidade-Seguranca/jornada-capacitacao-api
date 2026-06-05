<?php

namespace App\Providers;

use App\Repositories\Contracts\CompetencyRepositoryInterface;
use App\Repositories\Eloquent\CompetencyRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompetencyRepositoryInterface::class, CompetencyRepository::class);
    }
}
