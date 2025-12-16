<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(Product::getTypes());

        $names = [
            Product::TYPE_BOOK => ['Mathematics Book', 'Science Book', 'History Textbook', 'English Literature'],
            Product::TYPE_CAMP => ['Summer Camp', 'Winter Camp', 'Technology Camp', 'Art Workshop'],
            Product::TYPE_APPLICATION_FEE => ['Registration Fee', 'Application Fee', 'Enrollment Fee'],
            Product::TYPE_UNIFORM => ['School Uniform', 'Sports Uniform', 'Lab Coat'],
            Product::TYPE_MATERIALS => ['Lab Kit', 'Art Supplies', 'Science Materials', 'Stationery Pack'],
            Product::TYPE_OTHER => ['School Bag', 'ID Card', 'Transportation Pass'],
        ];

        $priceRanges = [
            Product::TYPE_BOOK => [30, 100],
            Product::TYPE_CAMP => [200, 500],
            Product::TYPE_APPLICATION_FEE => [50, 150],
            Product::TYPE_UNIFORM => [50, 120],
            Product::TYPE_MATERIALS => [80, 200],
            Product::TYPE_OTHER => [20, 100],
        ];

        return [
            'name' => fake()->randomElement($names[$type]),
            'price' => fake()->randomFloat(2, $priceRanges[$type][0], $priceRanges[$type][1]),
            'type' => $type,
            'is_active' => fake()->boolean(85), // 85% chance of being active
        ];
    }

    /**
     * Indicate that the product is a book.
     */
    public function book(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Product::TYPE_BOOK,
        ]);
    }

    /**
     * Indicate that the product is a camp.
     */
    public function camp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Product::TYPE_CAMP,
        ]);
    }

    /**
     * Indicate that the product is an application fee.
     */
    public function applicationFee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Product::TYPE_APPLICATION_FEE,
        ]);
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
