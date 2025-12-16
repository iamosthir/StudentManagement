<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExpenseCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Salaries',
            'Rent',
            'Utilities',
            'Office Supplies',
            'Marketing',
            'Transportation',
            'Maintenance',
            'Equipment',
            'Training',
            'Miscellaneous',
        ];

        return [
            'name' => fake()->unique()->randomElement($categories),
        ];
    }
}
