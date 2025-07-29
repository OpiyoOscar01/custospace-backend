<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\SubtaskController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// Protected routes (require auth)
Route::middleware('auth:sanctum')->group(function () {
    // Authenticated user info & logout
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    // Workspace routes
    Route::prefix('workspaces')->name('api.workspaces.')->group(function () {
        Route::get('/', [WorkspaceController::class, 'index'])->name('index');
        Route::post('/', [WorkspaceController::class, 'store'])->name('store');
        Route::get('/{workspace}', [WorkspaceController::class, 'show'])->name('show');
        Route::put('/{workspace}', [WorkspaceController::class, 'update'])->name('update');
        Route::delete('/{workspace}', [WorkspaceController::class, 'destroy'])->name('destroy');
        Route::patch('/{workspace}/activate', [WorkspaceController::class, 'activate'])->name('activate');
        Route::patch('/{workspace}/deactivate', [WorkspaceController::class, 'deactivate'])->name('deactivate');
        Route::post('/{workspace}/users', [WorkspaceController::class, 'assignUser'])->name('assign-user');
        Route::get('/{workspace}/teams', [TeamController::class, 'index'])->name('teams.index');
        Route::post('/{workspace}/teams', [TeamController::class, 'store'])->name('teams.store');
    });

    // Team routes
    Route::prefix('teams')->name('api.teams.')->group(function () {
        Route::get('/{team}', [TeamController::class, 'show'])->name('show');
        Route::put('/{team}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{team}', [TeamController::class, 'destroy'])->name('destroy');
        Route::patch('/{team}/activate', [TeamController::class, 'activate'])->name('activate');
        Route::patch('/{team}/deactivate', [TeamController::class, 'deactivate'])->name('deactivate');
        Route::post('/{team}/users', [TeamController::class, 'assignUser'])->name('assign-user');
    });
    // RESTful Project routes
    Route::apiResource('projects', ProjectController::class);

    // Custom Project actions
    Route::prefix('projects/{project}')->group(function () {
        // Status management
        Route::patch('/activate', [ProjectController::class, 'activate'])->name('projects.activate');
        Route::patch('/deactivate', [ProjectController::class, 'deactivate'])->name('projects.deactivate');
        Route::patch('/complete', [ProjectController::class, 'complete'])->name('projects.complete');
        Route::patch('/cancel', [ProjectController::class, 'cancel'])->name('projects.cancel');
        
        // Progress management
        Route::patch('/progress', [ProjectController::class, 'updateProgress'])->name('projects.progress');
        
        // User management
        Route::post('/assign-user', [ProjectController::class, 'assignUser'])->name('projects.assign-user');
        Route::delete('/remove-user', [ProjectController::class, 'removeUser'])->name('projects.remove-user');
        Route::patch('/update-user-role', [ProjectController::class, 'updateUserRole'])->name('projects.update-user-role');
        });

        // Project statistics
        Route::get('projects-statistics', [ProjectController::class, 'statistics'])->name('projects.statistics');

        // RESTful Status routes
        Route::apiResource('statuses', StatusController::class);
        
        // Custom Status routes
        Route::get('workspaces/{workspaceId}/statuses', [StatusController::class, 'byWorkspace']);
        Route::get('statuses/type/{type}', [StatusController::class, 'byType']);
        Route::post('statuses/create-default', [StatusController::class, 'createDefaultStatuses']);

        /*
        |--------------------------------------------------------------------------
        | Pipeline API Routes
        |--------------------------------------------------------------------------
        */

        // RESTful Pipeline routes
        Route::apiResource('pipelines', PipelineController::class);
        
        // Custom Pipeline routes
        Route::get('workspaces/{workspaceId}/pipelines', [PipelineController::class, 'byWorkspace']);
        Route::get('projects/{projectId}/pipelines', [PipelineController::class, 'byProject']);
        
        // Pipeline management routes
        Route::prefix('pipelines/{pipeline}')->group(function () {
            Route::patch('/set-default', [PipelineController::class, 'setAsDefault']);
            Route::post('/sync-statuses', [PipelineController::class, 'syncStatuses']);
            Route::post('/add-status', [PipelineController::class, 'addStatus']);
            Route::delete('/remove-status', [PipelineController::class, 'removeStatus']);
            Route::patch('/reorder-statuses', [PipelineController::class, 'reorderStatuses']);
        });
        
        Route::post('pipelines/create-default', [PipelineController::class, 'createDefaultPipelineForWorkspace']);
        

        // Project status and pipeline management
        Route::prefix('projects/{project}')->group(function () {
            Route::get('/default-pipeline', [ProjectController::class, 'getDefaultPipeline']);
            Route::post('/pipelines', [ProjectController::class, 'createPipeline']);
            Route::patch('/status', [ProjectController::class, 'updateStatus']);
        });
         
         // Task Routes
        Route::apiResource('tasks', TaskController::class);
        
        // Task custom actions
        Route::patch('tasks/{task}/status', [TaskController::class, 'changeStatus']);
        Route::patch('tasks/{task}/assign', [TaskController::class, 'assignTask']);
        Route::post('tasks/{task}/dependencies', [TaskController::class, 'addDependency']);
        Route::delete('tasks/{task}/dependencies', [TaskController::class, 'removeDependency']);
        Route::put('tasks/{task}/milestones', [TaskController::class, 'syncMilestones']);
        
        // Subtask Routes
        Route::get('tasks/{task}/subtasks', [SubtaskController::class, 'index']);
        Route::apiResource('subtasks', SubtaskController::class)->except(['index']);
        Route::patch('subtasks/{subtask}/toggle-completion', [SubtaskController::class, 'toggleCompletion']);
        Route::post('tasks/{task}/subtasks/reorder', [SubtaskController::class, 'reorder']);
        
        // Milestone Routes
        Route::apiResource('milestones', MilestoneController::class);
        Route::get('projects/{project}/milestones', [MilestoneController::class, 'byProject']);
        Route::patch('milestones/{milestone}/toggle-completion', [MilestoneController::class, 'toggleCompletion']);
        Route::put('milestones/{milestone}/tasks', [MilestoneController::class, 'syncTasks']);
        Route::post('projects/{project}/milestones/reorder', [MilestoneController::class, 'reorder']);

        // Tag Routes
        Route::apiResource('tags', TagController::class);
    
        Route::post('tags/{tag}/assign-to-task', [TagController::class, 'assignToTask']);
        Route::post('tags/{tag}/remove-from-task', [TagController::class, 'removeFromTask']);
        Route::get('tasks/tags', [TagController::class, 'getByTask']);

});
