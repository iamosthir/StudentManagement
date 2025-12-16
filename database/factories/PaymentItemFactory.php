<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Product;
use App\Models\SubscriptionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentItem>
 */
class PaymentItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $itemType = fake()->randomElement([PaymentItem::TYPE_SUBSCRIPTION, PaymentItem::TYPE_PRODUCT]);
        $quantity = $itemType === PaymentItem::TYPE_PRODUCT ? fake()->numberBetween(1, 5) : 1;
        $unitPrice = fake()->randomFloat(2, 50, 500);
        $lineTotal = $unitPrice * $quantity;
        $discountValue = fake()->boolean(30) ? fake()->randomFloat(2, 10, $lineTotal * 0.3) : 0;
        $totalPrice = $lineTotal - $discountValue;

        $description = $itemType === PaymentItem::TYPE_SUBSCRIPTION
            ? 'Program: ' . fake()->words(2, true) . ' – ' . fake()->words(3, true)
            : fake()->words(3, true) . ' (' . fake()->word() . ')';

        return [
            'payment_id' => Payment::factory(),
            'item_type' => $itemType,
            'item_id' => $itemType === PaymentItem::TYPE_SUBSCRIPTION
                ? SubscriptionOption::factory()
                : Product::factory(),
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_value' => $discountValue,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Indicate that the item is a subscription.
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => PaymentItem::TYPE_SUBSCRIPTION,
            'item_id' => SubscriptionOption::factory(),
            'quantity' => 1,
            'description' => 'Program: ' . fake()->words(2, true) . ' – ' . fake()->words(3, true),
        ]);
    }

    /**
     * Indicate that the item is a product.
     */
    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => PaymentItem::TYPE_PRODUCT,
            'item_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'description' => fake()->words(3, true) . ' (' . fake()->word() . ')',
        ]);
    }

    /**
     * Indicate that the item has no discount.
     */
    public function noDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $lineTotal = $attributes['unit_price'] * $attributes['quantity'];
            return [
                'discount_value' => 0,
                'total_price' => $lineTotal,
            ];
        });
    }
}
