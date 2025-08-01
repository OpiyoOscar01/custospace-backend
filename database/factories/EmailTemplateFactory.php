<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Class EmailTemplateFactory
 * 
 * Factory for creating email template test instances
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = EmailTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        
        return [
            'workspace_id' => $this->faker->boolean(70) ? Workspace::factory() : null,
            'name' => $name,
            'slug' => Str::slug($name),
            'subject' => $this->faker->sentence(),
            'content' => $this->generateEmailContent(),
            'type' => $this->faker->randomElement(['system', 'custom']),
            'is_active' => $this->faker->boolean(80),
        ];
    }

    /**
     * State for system templates
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'system',
            'workspace_id' => null,
        ]);
    }

    /**
     * State for custom templates
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'custom',
            'workspace_id' => Workspace::factory(),
        ]);
    }

    /**
     * State for active templates
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * State for inactive templates
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * State for invitation templates
     */
    public function invitation(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Workspace Invitation',
            'slug' => 'workspace-invitation',
            'subject' => 'You\'ve been invited to join {{workspace_name}}',
            'content' => $this->generateInvitationContent(),
        ]);
    }

    /**
     * State for welcome templates
     */
    public function welcome(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Welcome Email',
            'slug' => 'welcome-email',
            'subject' => 'Welcome to {{workspace_name}}!',
            'content' => $this->generateWelcomeContent(),
        ]);
    }

    /**
     * Generate realistic email content
     * 
     * @return string
     */
    protected function generateEmailContent(): string
    {
        return "
            <h1>{{title}}</h1>
            <p>Dear {{user_name}},</p>
            <p>{$this->faker->paragraph()}</p>
            <p>{$this->faker->paragraph()}</p>
            <div style='background-color: #f5f5f5; padding: 20px; margin: 20px 0;'>
                <h3>Important Information</h3>
                <ul>
                    <li>{$this->faker->sentence()}</li>
                    <li>{$this->faker->sentence()}</li>
                </ul>
            </div>
            <p>Best regards,<br>{{sender_name}}</p>
            <hr>
            <p><small>This email was sent from {{workspace_name}}.</small></p>
        ";
    }

    /**
     * Generate invitation email content
     * 
     * @return string
     */
    protected function generateInvitationContent(): string
    {
        return "
            <h1>You've been invited!</h1>
            <p>Dear {{user_email}},</p>
            <p>{{inviter_name}} has invited you to join <strong>{{workspace_name}}</strong> as a {{role}}.</p>
            <p>Click the button below to accept your invitation:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{{invitation_url}}' style='background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;'>Accept Invitation</a>
            </div>
            <p>This invitation will expire on {{expires_at}}.</p>
            <p>If you have any questions, please contact {{inviter_email}}.</p>
        ";
    }

    /**
     * Generate welcome email content
     * 
     * @return string
     */
    protected function generateWelcomeContent(): string
    {
        return "
            <h1>Welcome to {{workspace_name}}!</h1>
            <p>Hi {{user_name}},</p>
            <p>We're excited to have you join our workspace. Here's what you can do to get started:</p>
            <ol>
                <li>Complete your profile setup</li>
                <li>Explore your dashboard</li>
                <li>Connect with your team members</li>
                <li>Set up your preferences</li>
            </ol>
            <p>If you need help getting started, check out our <a href='{{help_url}}'>help documentation</a> or contact support.</p>
            <p>Welcome aboard!</p>
        ";
    }
}