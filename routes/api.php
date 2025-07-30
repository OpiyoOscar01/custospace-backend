<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MentionController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\SubtaskController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\RecurringTaskController;
use App\Http\Controllers\Api\TimeLogController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReactionController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\CustomFieldController;
use App\Http\Controllers\Api\CustomFieldValueController;
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

        //Comment Routes
        Route::apiResource('comments', CommentController::class);
        Route::get('comments/by-commentable', [CommentController::class, 'getByCommentable']);
        Route::patch('comments/{comment}/toggle-internal', [CommentController::class, 'toggleInternal']);

        // Conversation Routes
        Route::apiResource('conversations', ConversationController::class);
    
        // Additional conversation routes
        Route::post('conversations/{conversation}/users', [ConversationController::class, 'addUsers']);
        Route::delete('conversations/{conversation}/users', [ConversationController::class, 'removeUsers']);
        Route::patch('conversations/{conversation}/users/role', [ConversationController::class, 'updateUserRole']);
        Route::post('conversations/{conversation}/read', [ConversationController::class, 'markAsRead']);
        Route::post('conversations/direct', [ConversationController::class, 'createDirectConversation']);

        // Message Routes
        Route::apiResource('messages', MessageController::class);
        Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
        Route::get('conversations/{conversation}/messages/after', [MessageController::class, 'getMessagesAfter']);

        //Mention Routes
        Route::get('mentions', [MentionController::class, 'index']);
        Route::get('mentions/unread-count', [MentionController::class, 'getUnreadCount']);
        Route::post('mentions/mark-all-read', [MentionController::class, 'markAllAsRead']);
        Route::get('mentions/{mention}', [MentionController::class, 'show']);
        Route::patch('mentions/{mention}/read', [MentionController::class, 'markAsRead']);
        Route::delete('mentions/{mention}', [MentionController::class, 'destroy']);

        // Attachment Routes
        Route::apiResource('attachments', AttachmentController::class);
        Route::prefix('attachments')->controller(AttachmentController::class)->group(function () {
            Route::get('{attachment}/download', 'download')->name('attachments.download');
            Route::patch('{attachment}/metadata', 'updateMetadata')->name('attachments.updateMetadata');
            Route::patch('{attachment}/move', 'moveToAttachable')->name('attachments.moveToAttachable');
        });

        // Media Routes
        Route::apiResource('media', MediaController::class);
        Route::prefix('media')->controller(MediaController::class)->group(function () {
            Route::patch('{media}/collection', 'moveToCollection')->name('media.moveToCollection');
            Route::patch('{media}/metadata', 'updateMetadata')->name('media.updateMetadata');
            Route::post('{media}/duplicate', 'duplicate')->name('media.duplicate');
        });

        // Goal Management Routes
        Route::prefix('goals')->controller(GoalController::class)->group(function () {
            // RESTful routes
            Route::get('/', 'index')->name('goals.index');
            Route::post('/', 'store')->name('goals.store');
            Route::get('{goal}', 'show')->name('goals.show');
            Route::put('{goal}', 'update')->name('goals.update');
            Route::patch('{goal}', 'update')->name('goals.patch');
            Route::delete('{goal}', 'destroy')->name('goals.destroy');
            
            // Custom action routes
            Route::patch('{goal}/activate', 'activate')->name('goals.activate');
            Route::patch('{goal}/complete', 'complete')->name('goals.complete');
            Route::patch('{goal}/cancel', 'cancel')->name('goals.cancel');
            Route::patch('{goal}/progress', 'updateProgress')->name('goals.update-progress');
            Route::post('{goal}/assign-user', 'assignUser')->name('goals.assign-user');
            Route::post('{goal}/assign-tasks', 'assignTasks')->name('goals.assign-tasks');
            });
                    /*
            |--------------------------------------------------------------------------
            | Time Log Routes
            |--------------------------------------------------------------------------
            */

            Route::prefix('time-logs')->controller(TimeLogController::class)->group(function () {
                Route::get('/', 'index');                          // GET /api/time-logs
                Route::post('/', 'store');                         // POST /api/time-logs
                Route::get('/summary', 'summary');                 // GET /api/time-logs/summary
                Route::get('/billable', 'billable');              // GET /api/time-logs/billable
                Route::post('/start', 'start');                   // POST /api/time-logs/start
                Route::get('/{time_log}', 'show');                // GET /api/time-logs/{id}
                Route::put('/{time_log}', 'update');              // PUT /api/time-logs/{id}
                Route::delete('/{time_log}', 'destroy');          // DELETE /api/time-logs/{id}
                Route::post('/{time_log}/stop', 'stop');          // POST /api/time-logs/{id}/stop
            });

            /*
            |--------------------------------------------------------------------------
            | Recurring Task Routes
            |--------------------------------------------------------------------------
            */

            Route::prefix('recurring-tasks')->controller(RecurringTaskController::class)->group(function () {
                Route::get('/', 'index');                                    // GET /api/recurring-tasks
                Route::post('/', 'store');                                   // POST /api/recurring-tasks
                Route::get('/due', 'due');                                   // GET /api/recurring-tasks/due
                Route::post('/process-due', 'processDue');                   // POST /api/recurring-tasks/process-due
                Route::get('/{recurring_task}', 'show');                     // GET /api/recurring-tasks/{id}
                Route::put('/{recurring_task}', 'update');                   // PUT /api/recurring-tasks/{id}
                Route::delete('/{recurring_task}', 'destroy');               // DELETE /api/recurring-tasks/{id}
                Route::patch('/{recurring_task}/activate', 'activate');      // PATCH /api/recurring-tasks/{id}/activate
                Route::patch('/{recurring_task}/deactivate', 'deactivate');  // PATCH /api/recurring-tasks/{id}/deactivate
                Route::patch('/{recurring_task}/update-next-due-date', 'updateNextDueDate'); // PATCH /api/recurring-tasks/{id}/update-next-due-date
            });


            /*
            |--------------------------------------------------------------------------
            | Reminder API Routes
            |--------------------------------------------------------------------------
            */

            Route::prefix('reminders')->controller(ReminderController::class)->group(function () {
                // RESTful routes
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('{reminder}', 'show');
                Route::put('{reminder}', 'update');
                Route::delete('{reminder}', 'destroy');
                
                // Custom routes
                Route::patch('{reminder}/activate', 'activate');
                Route::patch('{reminder}/deactivate', 'deactivate');
                Route::get('user/my-reminders', 'userReminders');
                Route::post('process-pending', 'processPending');
            });

            /*
            |--------------------------------------------------------------------------
            | Notification API Routes
            |--------------------------------------------------------------------------
            */

            Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
                // RESTful routes
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('{notification}', 'show');
                Route::put('{notification}', 'update');
                Route::delete('{notification}', 'destroy');
                
                // Custom routes
                Route::patch('{notification}/read', 'markAsRead');
                Route::patch('{notification}/unread', 'markAsUnread');
                Route::get('user/my-notifications', 'userNotifications');
                Route::get('user/unread', 'unreadNotifications');
                Route::patch('user/mark-all-read', 'markAllAsRead');
                Route::get('user/unread-count', 'unreadCount');
            });

                    // Activity Logs Routes
            Route::prefix('activity-logs')->controller(ActivityLogController::class)->group(function () {
                Route::get('/', 'index')->name('activity-logs.index');
                Route::post('/', 'store')->name('activity-logs.store');
                Route::get('/{activity_log}', 'show')->name('activity-logs.show');
                Route::put('/{activity_log}', 'update')->name('activity-logs.update');
                Route::delete('/{activity_log}', 'destroy')->name('activity-logs.destroy');
                
                // Custom endpoints
                Route::get('/workspace/{workspaceId}', 'getWorkspaceActivities')->name('activity-logs.workspace');
                Route::get('/statistics', 'getStatistics')->name('activity-logs.statistics');
                Route::post('/cleanup', 'cleanup')->name('activity-logs.cleanup');
                Route::post('/bulk', 'bulkStore')->name('activity-logs.bulk-store');
            });

            // Audit Logs Routes
            Route::prefix('audit-logs')->controller(AuditLogController::class)->group(function () {
                Route::get('/', 'index')->name('audit-logs.index');
                Route::post('/', 'store')->name('audit-logs.store');
                Route::get('/{audit_log}', 'show')->name('audit-logs.show');
                Route::put('/{audit_log}', 'update')->name('audit-logs.update');
                Route::delete('/{audit_log}', 'destroy')->name('audit-logs.destroy');
                
                // Custom endpoints
                Route::get('/trail', 'getAuditTrail')->name('audit-logs.trail');
                Route::get('/{audit_log}/changes', 'getFormattedChanges')->name('audit-logs.changes');
                Route::post('/cleanup', 'cleanup')->name('audit-logs.cleanup');
                Route::get('/event/{event}', 'getByEvent')->name('audit-logs.by-event');
            });

            // Reactions Routes
            Route::prefix('reactions')->controller(ReactionController::class)->group(function () {
                Route::get('/', 'index')->name('reactions.index');
                Route::post('/', 'store')->name('reactions.store');
                Route::get('/{reaction}', 'show')->name('reactions.show');
                Route::put('/{reaction}', 'update')->name('reactions.update');
                Route::delete('/{reaction}', 'destroy')->name('reactions.destroy');
                
                // Custom endpoints
                Route::post('/toggle', 'toggle')->name('reactions.toggle');
                Route::get('/item', 'getItemReactions')->name('reactions.item');
                Route::get('/summary', 'getReactionSummary')->name('reactions.summary');
                Route::get('/user', 'getUserReactions')->name('reactions.user');
                Route::post('/bulk-toggle', 'bulkToggle')->name('reactions.bulk-toggle');
                Route::get('/types', 'getAvailableTypes')->name('reactions.types');
            });

            // Custom Fields Routes
            Route::prefix('custom-fields')->controller(CustomFieldController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('by-entity', 'getByEntity');
                Route::patch('update-order', 'updateOrder');
                
                Route::get('{customField}', 'show');
                Route::put('{customField}', 'update');
                Route::delete('{customField}', 'destroy');
                Route::post('{customField}/duplicate', 'duplicate');
            });

            // Custom Field Values Routes  
            Route::prefix('custom-field-values')->controller(CustomFieldValueController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::post('bulk-store', 'bulkStore');
                Route::get('by-entity', 'getByEntity');
                
                Route::get('{customFieldValue}', 'show');
                Route::put('{customFieldValue}', 'update');
                Route::delete('{customFieldValue}', 'destroy');
            });


});
