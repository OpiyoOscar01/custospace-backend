<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * WebhookDelivery Factory
 * 
 * Creates fake webhook delivery instances for testing
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(WebhookDelivery::getStatuses());
        
        return [
            'webhook_id' => Webhook::factory(),
            'event' => $this->faker->randomElement([
                'user.created',
                'user.updated',
                'user.deleted',
                'order.created',
                'order.completed',
                'payment.received',
                'invoice.generated',
            ]),
            'payload' => [
                'id' => $this->faker->uuid(),
                'timestamp' => $this->faker->dateTimeThisMonth()->format('c'),
                'data' => [
                    'user_id' => $this->faker->numberBetween(1, 1000),
                    'email' => $this->faker->email(),
                    'action' => $this->faker->word(),
                ],
            ],
            'response_code' => $this->getResponseCode($status),
            'response_body' => $this->getResponseBody($status),
            'status' => $status,
            'attempts' => $this->getAttempts($status),
            'next_attempt_at' => $this->getNextAttemptAt($status),
        ];
    }

    /**
     * State for pending deliveries
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookDelivery::STATUS_PENDING,
            'response_code' => null,
            'response_body' => null,
            'attempts' => 0,
            'next_attempt_at' => now()->addMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }

    /**
     * State for delivered deliveries
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookDelivery::STATUS_DELIVERED,
            'response_code' => $this->faker->randomElement([200, 201, 202, 204]),
            'response_body' => json_encode(['success' => true, 'message' => 'Webhook processed successfully']),
            'attempts' => $this->faker->numberBetween(1, 3),
            'next_attempt_at' => null,
        ]);
    }

    /**
     * State for failed deliveries
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookDelivery::STATUS_FAILED,
            'response_code' => $this->faker->randomElement([400, 404, 500, 502, 503]),
            'response_body' => json_encode(['error' => 'Failed to process webhook', 'message' => $this->faker->sentence()]),
            'attempts' => $this->faker->numberBetween(1, 5),
            'next_attempt_at' => now()->addMinutes(pow(2, $this->faker->numberBetween(1, 5))),
        ]);
    }

    /**
     * State for deliveries ready for retry
     */
    public function readyForRetry(): static
    {
        return $this->failed()->state(fn (array $attributes) => [
            'next_attempt_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
            'attempts' => $this->faker->numberBetween(1, 4), // Less than max attempts
        ]);
    }

    /**
     * Get response code based on status
     */
    private function getResponseCode(string $status): ?int
    {
        return match ($status) {
            WebhookDelivery::STATUS_PENDING => null,
            WebhookDelivery::STATUS_DELIVERED => $this->faker->randomElement([200, 201, 202, 204]),
            WebhookDelivery::STATUS_FAILED => $this->faker->randomElement([400, 404, 500, 502, 503]),
        };
    }

    /**
     * Get response body based on status
     */
    private function getResponseBody(string $status): ?string
    {
        return match ($status) {
            WebhookDelivery::STATUS_PENDING => null,
            WebhookDelivery::STATUS_DELIVERED => json_encode(['success' => true, 'message' => 'Webhook processed successfully']),
            WebhookDelivery::STATUS_FAILED => json_encode(['error' => 'Failed to process webhook', 'message' => $this->faker->sentence()]),
        };
    }

    /**
     * Get attempts based on status
     */
    private function getAttempts(string $status): int
    {
        return match ($status) {
            WebhookDelivery::STATUS_PENDING => 0,
            WebhookDelivery::STATUS_DELIVERED => $this->faker->numberBetween(1, 3),
            WebhookDelivery::STATUS_FAILED => $this->faker->numberBetween(1, 5),
        };
    }

    /**
     * Get next attempt time based on status
     */
    private function getNextAttemptAt(string $status): ?\Illuminate\Support\Carbon
    {
        return match ($status) {
            WebhookDelivery::STATUS_PENDING => now()->addMinutes($this->faker->numberBetween(1, 60)),
            WebhookDelivery::STATUS_DELIVERED => null,
            WebhookDelivery::STATUS_FAILED => now()->addMinutes(pow(2, $this->faker->numberBetween(1, 5))),
        };
    }
}
