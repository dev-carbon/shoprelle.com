<?php

namespace Database\Factories;

use App\Enums\Marketplace;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $marketplace = fake()->randomElement(Marketplace::cases());
        $domain = $marketplace->domains()[0] ?? 'example.com';

        return [
            'purchase_request_id' => PurchaseRequest::factory(),
            'marketplace' => $marketplace,
            'product_url' => 'https://'.$domain.'/product/'.fake()->numerify('########').'.html',
            'product_name' => fake()->optional()->words(3, true),
            'quantity' => fake()->numberBetween(1, 3),
            'color' => fake()->optional()->safeColorName(),
            'size' => fake()->optional()->randomElement(['S', 'M', 'L', 'XL', '40', '42', '44']),
            'variant' => null,
            'declared_price' => fake()->optional()->randomFloat(2, 5, 200),
            'declared_currency' => config('shoprelle.declared_currency'),
            'comment' => fake()->optional()->sentence(),
            'position' => 0,
        ];
    }

    /**
     * Anchor the item to a specific marketplace with a matching URL.
     */
    public function on(Marketplace $marketplace): static
    {
        return $this->state(function (array $attributes) use ($marketplace): array {
            $domain = $marketplace->domains()[0] ?? 'example.com';

            return [
                'marketplace' => $marketplace,
                'product_url' => 'https://'.$domain.'/product/'.fake()->numerify('########').'.html',
            ];
        });
    }
}
