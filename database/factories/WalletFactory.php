<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(Wallet::getTypes());

        $names = [
            Wallet::TYPE_STAFF => ['Omar Tech', 'Ahmed Sales', 'Sara Admin', 'Staff Wallet'],
            Wallet::TYPE_MAIN_CASHBOX => ['Main Cashbox', 'Central Treasury', 'Main Vault'],
            Wallet::TYPE_EXPENSE => ['Expense Wallet 1', 'Operations Expense', 'Petty Cash'],
        ];

        return [
            'owner_type' => Admin::class,
            'owner_id' => Admin::factory(),
            'name' => fake()->randomElement($names[$type]),
            'type' => $type,
            'receivable_amount' => fake()->randomFloat(2, 0, 50000),
            'payable_amount' => fake()->randomFloat(2, 0, 20000),
        ];
    }

    /**
     * Indicate that the wallet is a staff wallet.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Wallet::TYPE_STAFF,
            'name' => fake()->name() . ' Wallet',
        ]);
    }

    /**
     * Indicate that the wallet is the main cashbox.
     */
    public function mainCashbox(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Wallet::TYPE_MAIN_CASHBOX,
            'name' => 'Main Cashbox',
        ]);
    }

    /**
     * Indicate that the wallet is an expense wallet.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Wallet::TYPE_EXPENSE,
            'name' => 'Expense Wallet ' . fake()->numberBetween(1, 10),
        ]);
    }
}
