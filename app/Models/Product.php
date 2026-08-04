<?php

namespace App\Models;

use App\Enums\Marketplace;
use App\Enums\ProductCategory;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Un produit de la sélection montrée sur la vitrine.
 *
 * Voir la migration pour ce que cette table est et n'est pas : une vitrine
 * tenue à la main, pas un catalogue.
 *
 * @property int $id
 * @property string $name
 * @property string|null $image_path
 * @property Marketplace $marketplace
 * @property ProductCategory $category
 * @property string $product_url
 * @property string|null $price
 * @property string|null $currency
 * @property bool $is_featured
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marketplace' => Marketplace::class,
            'category' => ProductCategory::class,
            'is_featured' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Ce que la vitrine montre : les produits mis en avant, dans leur ordre.
     *
     * L'identifiant départage deux produits de même rang, sans quoi l'ordre
     * dépend de ce que la base rend et change d'un chargement à l'autre.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * L'URL publique de la photo, ou `null` si le produit n'en a pas encore.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path === null
            ? null
            : Storage::disk('public')->url($this->image_path);
    }
}
