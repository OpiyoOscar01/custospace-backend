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
use App\Repositories\MilestoneRepository;
use App\Repositories\SubtaskRepository;
use App\Repositories\Contracts\SubtaskRepositoryInterface;
use App\Repositories\Contracts\MilestoneRepositoryInterface;
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
        $this->app->bind(StatusRepositoryInterface::class, StatusRepository::class);
        $this->app->bind(PipelineRepositoryInterface::class, PipelineRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(SubtaskRepositoryInterface::class, SubtaskRepository::class);
        $this->app->bind(MilestoneRepositoryInterface::class, MilestoneRepository::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
