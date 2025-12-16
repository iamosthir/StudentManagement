<?php

namespace Database\Factories;

use App\Models\SubscriptionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionOption>
 */
class SubscriptionOptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionOption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $durations = [1, 3, 6, 12];
        $duration = fake()->randomElement($durations);
        $isFullPayment = $duration >= 12;

        return [
            'name' => fake()->randomElement([
                'Monthly Payment',
                'Quarterly Payment',
                'Semester Payment',
                'Annual Payment',
                'Monthly Advance',
                'Full Year Payment',
            ]),
            'price' => fake()->randomFloat(2, 300, 6000),
            'duration_months' => $duration,
            'is_full_payment' => $isFullPayment,
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the subscription is a full payment option.
     */
    public function fullPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_full_payment' => true,
            'duration_months' => 12,
        ]);
    }

    /**
     * Indicate that the subscription is an installment option.
     */
    public function installment(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_full_payment' => false,
            'duration_months' => fake()->randomElement([1, 3, 6]),
        ]);
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
