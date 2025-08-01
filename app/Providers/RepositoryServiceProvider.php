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
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\TagRepository;
use App\Repositories\CommentRepository;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\ConversationRepository;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\MessageRepository;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\MentionRepository;
use App\Repositories\Contracts\MentionRepositoryInterface;
use App\Repositories\AttachmentRepository;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\MediaRepository;
use App\Repositories\Contracts\GoalRepositoryInterface;
use App\Repositories\Contracts\RecurringTaskRepositoryInterface;
use App\Repositories\Contracts\TimeLogRepositoryInterface;
use App\Repositories\RecurringTaskRepository;
use App\Repositories\TimeLogRepository;
use App\Repositories\GoalRepository;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\ReminderRepositoryInterface;
use App\Repositories\NotificationRepository;
use App\Repositories\ReminderRepository;
use App\Repositories\ActivityLogRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\ReactionRepositoryInterface;
use App\Repositories\Contracts\CustomFieldRepositoryInterface;
use App\Repositories\Contracts\CustomFieldValueRepositoryInterface;
use App\Repositories\CustomFieldRepository;
use App\Repositories\CustomFieldValueRepository;
use App\Repositories\ReactionRepository;
use App\Repositories\Contracts\FormRepositoryInterface;
use App\Repositories\Contracts\FormResponseRepositoryInterface;
use App\Repositories\FormRepository;
use App\Repositories\FormResponseRepository;
use App\Repositories\Contracts\WikiRepositoryInterface;
use App\Repositories\Contracts\EventParticipantRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\EventParticipantRepository;
use App\Repositories\EventRepository;
use App\Repositories\WikiRepository;
use App\Repositories\ApiTokenRepository;
use App\Repositories\Contracts\ApiTokenRepositoryInterface;
use App\Repositories\Contracts\UserPreferenceRepositoryInterface;
use App\Repositories\UserPreferenceRepository;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\IntegrationRepository;
use App\Repositories\PlanRepository;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\InvoiceRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\Contracts\EmailTemplateRepositoryInterface;
use App\Repositories\Contracts\InvitationRepositoryInterface;
use App\Repositories\EmailTemplateRepository;
use App\Repositories\InvitationRepository;
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
        $this->app->bind(TagRepositoryInterface::class, TagRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
        $this->app->bind(ConversationRepositoryInterface::class, ConversationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);
        $this->app->bind(MentionRepositoryInterface::class, MentionRepository::class);
        $this->app->bind(AttachmentRepositoryInterface::class, AttachmentRepository::class);
        $this->app->bind(MediaRepositoryInterface::class, MediaRepository::class);
        $this->app->bind( GoalRepositoryInterface::class,GoalRepository::class);
        $this->app->bind(TimeLogRepositoryInterface::class,TimeLogRepository::class);
        $this->app->bind(RecurringTaskRepositoryInterface::class,RecurringTaskRepository::class);
        $this->app->bind(ReminderRepositoryInterface::class,ReminderRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class,NotificationRepository::class);
        $this->app->bind(ActivityLogRepositoryInterface::class,ActivityLogRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class,AuditLogRepository::class);
        $this->app->bind(ReactionRepositoryInterface::class,ReactionRepository::class);
        $this->app->bind(CustomFieldRepositoryInterface::class,CustomFieldRepository::class);
        $this->app->bind(CustomFieldValueRepositoryInterface::class,CustomFieldValueRepository::class);
        $this->app->bind(FormRepositoryInterface::class,FormRepository::class);
        $this->app->bind(FormResponseRepositoryInterface::class,FormResponseRepository::class);
        $this->app->bind(WikiRepositoryInterface::class,WikiRepository::class);
        $this->app->bind(EventRepositoryInterface::class,EventRepository::class);
        $this->app->bind(EventParticipantRepositoryInterface::class,EventParticipantRepository::class);
        $this->app->bind(UserPreferenceRepositoryInterface::class,UserPreferenceRepository::class);
        $this->app->bind(ApiTokenRepositoryInterface::class,ApiTokenRepository::class);
        $this->app->bind(IntegrationRepositoryInterface::class,IntegrationRepository::class);
        $this->app->bind(PlanRepositoryInterface::class,PlanRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class,SubscriptionRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class,InvoiceRepository::class);
        $this->app->bind(InvitationRepositoryInterface::class,InvitationRepository::class);
        $this->app->bind(EmailTemplateRepositoryInterface::class,EmailTemplateRepository::class);
    }


    

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
