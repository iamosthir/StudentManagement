<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WalletTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transactionType = fake()->randomElement([
            WalletTransaction::TYPE_PAYMENT_IN,
            WalletTransaction::TYPE_TRANSFER_IN,
            WalletTransaction::TYPE_TRANSFER_OUT,
            WalletTransaction::TYPE_EXPENSE,
        ]);

        $direction = in_array($transactionType, [WalletTransaction::TYPE_PAYMENT_IN, WalletTransaction::TYPE_TRANSFER_IN])
            ? WalletTransaction::DIRECTION_IN
            : WalletTransaction::DIRECTION_OUT;

        $relatedPaymentId = $transactionType === WalletTransaction::TYPE_PAYMENT_IN
            ? Payment::factory()
            : null;

        return [
            'wallet_id' => Wallet::factory(),
            'related_payment_id' => $relatedPaymentId,
            'related_expense_id' => null, // Will be set if needed
            'transaction_type' => $transactionType,
            'amount' => fake()->randomFloat(2, 50, 5000),
            'direction' => $direction,
            'description' => fake()->sentence(),
            'created_by_admin_id' => fake()->boolean(80) ? Admin::factory() : null,
        ];
    }

    /**
     * Indicate that the transaction is a payment in.
     */
    public function paymentIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => WalletTransaction::TYPE_PAYMENT_IN,
            'direction' => WalletTransaction::DIRECTION_IN,
            'related_payment_id' => Payment::factory(),
        ]);
    }

    /**
     * Indicate that the transaction is a transfer in.
     */
    public function transferIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => WalletTransaction::TYPE_TRANSFER_IN,
            'direction' => WalletTransaction::DIRECTION_IN,
            'related_payment_id' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a transfer out.
     */
    public function transferOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => WalletTransaction::TYPE_TRANSFER_OUT,
            'direction' => WalletTransaction::DIRECTION_OUT,
            'related_payment_id' => null,
        ]);
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => WalletTransaction::TYPE_EXPENSE,
            'direction' => WalletTransaction::DIRECTION_OUT,
            'related_payment_id' => null,
        ]);
    }
}
