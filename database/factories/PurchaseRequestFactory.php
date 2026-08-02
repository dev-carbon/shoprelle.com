<?php

namespace Database\Factories;

use App\Enums\PurchaseRequestStatus;
use App\Models\Customer;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customer = Customer::factory();

        return [
            'reference' => PurchaseRequest::generateReference(),
            'customer_id' => $customer,
            'status' => PurchaseRequestStatus::New,
            'country' => 'CM',
            'city' => fake()->randomElement(['Douala', 'Yaoundé', 'Bafoussam']),
            'channel' => 'web',
            'customer_comment' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Put the request in a given status without going through the transitions.
     */
    public function status(PurchaseRequestStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate that a quote has already been sent to the customer.
     */
    public function quoted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PurchaseRequestStatus::QuoteSent,
            'quote_items_amount' => '45000.00',
            'quote_shipping_amount' => '15000.00',
            'quote_total_amount' => '60000.00',
            'quote_currency' => config('shoprelle.quote_currency'),
            'quote_sent_at' => now(),
        ]);
    }
}
