<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Account';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'service_level' => Account::SERVICE_LEVEL_SINGLE_AGENT,
            'billing_status' => Account::BILLING_STATUS_PENDING,
            'stripe_customer_id' => null,
            'stripe_subscription_id' => null,
            'trial_ends_at' => null,
        ];
    }

    public function activeBilling(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_status' => Account::BILLING_STATUS_ACTIVE,
        ]);
    }

    public function pendingBilling(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_status' => Account::BILLING_STATUS_PENDING,
        ]);
    }
}
