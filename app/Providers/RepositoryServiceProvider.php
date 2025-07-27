<?php

namespace App\Providers;

use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use WorkspaceRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(WorkspaceRepositoryInterface::class, WorkspaceRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
