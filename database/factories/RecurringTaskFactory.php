<?php

namespace Database\Factories;

use App\Models\RecurringTask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringTask>
 */
class RecurringTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RecurringTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frequency = $this->faker->randomElement([
            RecurringTask::FREQUENCY_DAILY,
            RecurringTask::FREQUENCY_WEEKLY,
            RecurringTask::FREQUENCY_MONTHLY,
            RecurringTask::FREQUENCY_YEARLY,
        ]);

        return [
            'task_id' => Task::factory(),
            'frequency' => $frequency,
            'interval' => $this->faker->numberBetween(1, 3),
            'days_of_week' => $this->getDaysOfWeekForFrequency($frequency),
            'day_of_month' => $this->getDayOfMonthForFrequency($frequency),
            'next_due_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->optional(0.3)->dateTimeBetween('+1 month', '+1 year'),
            'is_active' => $this->faker->boolean(0.8),
        ];
    }

    /**
     * Indicate that the recurring task is active.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the recurring task is inactive.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Indicate that the recurring task is due.
     */
    public function due(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'next_due_date' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'is_active' => true,
            ];
        });
    }

    /**
     * Create a daily recurring task.
     */
    public function daily(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'frequency' => RecurringTask::FREQUENCY_DAILY,
                'days_of_week' => null,
                'day_of_month' => null,
            ];
        });
    }

    /**
     * Create a weekly recurring task.
     */
    public function weekly(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'frequency' => RecurringTask::FREQUENCY_WEEKLY,
                'days_of_week' => $this->faker->randomElements([1, 2, 3, 4, 5, 6, 7], $this->faker->numberBetween(1, 3)),
                'day_of_month' => null,
            ];
        });
    }

    /**
     * Create a monthly recurring task.
     */
    public function monthly(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'frequency' => RecurringTask::FREQUENCY_MONTHLY,
                'days_of_week' => null,
                'day_of_month' => $this->faker->numberBetween(1, 28),
            ];
        });
    }

    /**
     * Create a yearly recurring task.
     */
    public function yearly(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'frequency' => RecurringTask::FREQUENCY_YEARLY,
                'days_of_week' => null,
                'day_of_month' => null,
            ];
        });
    }

    /**
     * Get days of week based on frequency.
     */
    protected function getDaysOfWeekForFrequency(string $frequency): ?array
    {
        if ($frequency === RecurringTask::FREQUENCY_WEEKLY) {
            return $this->faker->randomElements([1, 2, 3, 4, 5, 6, 7], $this->faker->numberBetween(1, 3));
        }

        return null;
    }

    /**
     * Get day of month based on frequency.
     */
    protected function getDayOfMonthForFrequency(string $frequency): ?int
    {
        if ($frequency === RecurringTask::FREQUENCY_MONTHLY) {
            return $this->faker->numberBetween(1, 28);
        }

        return null;
    }
}
