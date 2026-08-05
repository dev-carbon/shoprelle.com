<?php

namespace Database\Factories;

use App\Enums\Marketplace;
use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $marketplace = fake()->randomElement(Marketplace::cases());

        return [
            'name' => rtrim(fake()->sentence(3), '.'),
            'image_path' => 'products/'.fake()->uuid().'.webp',
            'marketplace' => $marketplace,
            'category' => fake()->randomElement(ProductCategory::cases()),
            'product_url' => 'https://www.'.($marketplace->domains()[0] ?? 'example.com').'/p/'.fake()->uuid(),
            'price' => fake()->numberBetween(3, 120) * 500,
            'currency' => config('shoprelle.quote_currency'),
            'is_featured' => true,
            'position' => 0,
        ];
    }

    /** Un produit retiré de la vitrine sans être supprimé. */
    public function hidden(): static
    {
        return $this->state(fn (): array => ['is_featured' => false]);
    }

    /** Un produit dont la photo n'a pas encore été déposée. */
    public function withoutImage(): static
    {
        return $this->state(fn (): array => ['image_path' => null]);
    }
}
