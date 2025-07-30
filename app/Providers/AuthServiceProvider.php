<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Media;
use App\Models\Pipeline;
use App\Models\Project;
use App\Models\Status;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\AttachmentPolicy;
use App\Policies\MediaPolicy;
use App\Policies\PipelinePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\StatusPolicy;
use App\Policies\TeamPolicy;
use App\Policies\WorkspacePolicy;
use App\Models\Goal;
use App\Policies\GoalPolicy;
use App\Models\RecurringTask;
use App\Models\TimeLog;
use App\Policies\RecurringTaskPolicy;
use App\Policies\TimeLogPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Workspace::class => WorkspacePolicy::class,
        Team::class => TeamPolicy::class,
        Project::class => ProjectPolicy::class,
        Status::class => StatusPolicy::class,
        Pipeline::class => PipelinePolicy::class,
        Media::class => MediaPolicy::class,
        Attachment::class => AttachmentPolicy::class,
        Project::class => ProjectPolicy::class,
        Goal::class => GoalPolicy::class,
        TimeLog::class => TimeLogPolicy::class,
        RecurringTask::class => RecurringTaskPolicy::class,


    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates if needed
    }
}
