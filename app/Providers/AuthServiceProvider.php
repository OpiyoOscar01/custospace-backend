<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Media;
use App\Models\Notification;
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
use App\Policies\ReminderPolicy;
use App\Policies\StatusPolicy;
use App\Policies\TeamPolicy;
use App\Policies\WorkspacePolicy;
use App\Models\Goal;
use App\Policies\GoalPolicy;
use App\Models\RecurringTask;
use App\Models\Reminder;
use App\Models\TimeLog;
use App\Policies\NotificationPolicy;
use App\Policies\RecurringTaskPolicy;
use App\Policies\TimeLogPolicy;
use App\Models\Form;
use App\Models\FormResponse;
use App\Policies\FormPolicy;
use App\Policies\FormResponsePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Policies\EventParticipantPolicy;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Policies\InvoicePolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\EventPolicy;
use App\Models\EmailTemplate;
use App\Models\Export;
use App\Models\Import;
use App\Models\Invitation;
use App\Models\Setting;
use App\Models\Webhook;
use App\Policies\EmailTemplatePolicy;
use App\Policies\ExportPolicy;
use App\Policies\ImportPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\SettingPolicy;
use App\Policies\WebhookDeliveryPolicy;


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
    Reminder::class => ReminderPolicy::class,
    Notification::class => NotificationPolicy::class,
    Form::class => FormPolicy::class,
    FormResponse::class => FormResponsePolicy::class,
    Event::class => EventPolicy::class,
    EventParticipant::class => EventParticipantPolicy::class,
    Subscription::class => SubscriptionPolicy::class,
    Invoice::class => InvoicePolicy::class,
    Invitation::class => InvitationPolicy::class,
    EmailTemplate::class => EmailTemplatePolicy::class,
    Webhook::class => WebhookDeliveryPolicy::class,
    Setting::class => SettingPolicy::class,
    Import::class => ImportPolicy::class,
    Export::class => ExportPolicy::class,



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

