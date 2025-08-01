<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'stripe_id' => 'in_' . $this->faker->unique()->regexify('[A-Za-z0-9]{14}'),
            'number' => $this->faker->unique()->numerify('INV-####'),
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'status' => $this->faker->randomElement(Invoice::getStatuses()),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'line_items' => $this->generateLineItems(),
        ];
    }

    /**
     * Generate sample line items.
     */
    private function generateLineItems(): array
    {
        $itemCount = $this->faker->numberBetween(1, 5);
        $items = [];

        for ($i = 0; $i < $itemCount; $i++) {
            $items[] = [
                'description' => $this->faker->sentence(),
                'amount' => $this->faker->randomFloat(2, 5, 1000),
                'quantity' => $this->faker->numberBetween(1, 10),
            ];
        }

        return $items;
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
        ]);
    }

    /**
     * Indicate that the invoice is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_OPEN,
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Indicate that the invoice is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_OPEN,
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
