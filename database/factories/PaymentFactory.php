<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_request_id' => PurchaseRequest::factory()->quoted(),
            'method' => PaymentMethod::MobileMoney,
            'provider' => fake()->randomElement(['Orange Money', 'MTN MoMo']),
            'amount' => '60000.00',
            'currency' => config('shoprelle.quote_currency'),
            'provider_reference' => fake()->bothify('MP######.####.?######'),
            'received_at' => now(),
            'recorded_by' => User::factory()->admin(),
            'notes' => null,
        ];
    }

    /**
     * A payment of an exact amount, for asserting on balances.
     */
    public function amount(string $amount): static
    {
        return $this->state(fn (array $attributes): array => [
            'amount' => $amount,
        ]);
    }

    public function method(PaymentMethod $method): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => $method,
            'provider' => null,
            'provider_reference' => $method->hasProviderReference()
                ? $attributes['provider_reference']
                : null,
        ]);
    }
}
