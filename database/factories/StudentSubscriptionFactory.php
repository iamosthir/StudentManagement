<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentSubscription>
 */
class StudentSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $durationMonths = fake()->randomElement([1, 3, 6, 12]);
        $expiryDate = (clone $startDate)->modify("+{$durationMonths} months");

        $hasDiscount = fake()->boolean(30);
        $discountType = $hasDiscount ? fake()->randomElement(['percent', 'fixed']) : null;
        $discountValue = $hasDiscount
            ? ($discountType === 'percent' ? fake()->numberBetween(5, 25) : fake()->randomFloat(2, 50, 200))
            : null;

        return [
            'student_id' => Student::factory(),
            'program_id' => Program::factory(),
            'subscription_option_id' => SubscriptionOption::factory(),
            'custom_price' => fake()->boolean(20) ? fake()->randomFloat(2, 300, 6000) : null,
            'coupon_code' => $hasDiscount ? strtoupper(fake()->bothify('COUP###??')) : null,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'start_date' => $startDate,
            'last_renewal_date' => fake()->boolean(50) ? fake()->dateTimeBetween($startDate, 'now') : null,
            'expiry_date' => $expiryDate,
            'status' => $expiryDate > now() ? StudentSubscription::STATUS_ACTIVE : StudentSubscription::STATUS_EXPIRED,
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentSubscription::STATUS_ACTIVE,
            'expiry_date' => now()->addMonths(fake()->numberBetween(1, 12)),
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentSubscription::STATUS_EXPIRED,
            'expiry_date' => now()->subDays(fake()->numberBetween(1, 60)),
        ]);
    }

    /**
     * Indicate that the subscription is expiring soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentSubscription::STATUS_ACTIVE,
            'expiry_date' => now()->addDays(fake()->numberBetween(1, 7)),
        ]);
    }
}
