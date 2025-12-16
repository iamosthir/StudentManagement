<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'expense_category_id' => ExpenseCategory::factory(),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'description' => fake()->sentence(),
            'created_by_admin_id' => fake()->boolean(80) ? Admin::factory() : null,
        ];
    }

    /**
     * Indicate that the expense is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the expense is from this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('first day of this month', 'now'),
        ]);
    }

    /**
     * Indicate that the expense is a large amount.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => fake()->randomFloat(2, 5000, 20000),
        ]);
    }
}
