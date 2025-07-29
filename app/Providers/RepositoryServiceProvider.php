<?php

namespace App\Providers;

use App\Repositories\Contracts\PipelineRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\StatusRepositoryInterface;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Repositories\PipelineRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\StatusRepository;
use App\Repositories\TeamRepository;
use App\Repositories\WorkspaceRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\TaskRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(WorkspaceRepositoryInterface::class, WorkspaceRepository::class);
        $this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);

        // Bind Status Repository
        $this->app->bind(StatusRepositoryInterface::class, StatusRepository::class);

        // Bind Pipeline Repository
        $this->app->bind(PipelineRepositoryInterface::class, PipelineRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
