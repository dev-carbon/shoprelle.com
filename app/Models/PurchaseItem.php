<?php

namespace App\Models;

use App\Enums\Marketplace;
use Database\Factories\PurchaseItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One product line inside a purchase request.
 *
 * Every descriptive field is supplied by the customer today. When automatic
 * product retrieval is introduced it will populate these same columns, so
 * nothing downstream has to change.
 *
 * @property int $id
 * @property int $purchase_request_id
 * @property Marketplace $marketplace
 * @property string $product_url
 * @property string|null $product_name
 * @property int $quantity
 * @property string|null $color
 * @property string|null $size
 * @property string|null $variant
 * @property string|null $declared_price
 * @property string|null $declared_currency
 * @property string|null $quoted_amount
 * @property string|null $comment
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseRequest $purchaseRequest
 * @property-read Collection<int, Attachment> $attachments
 */
#[Fillable([
    'purchase_request_id',
    'marketplace',
    'product_url',
    'product_name',
    'quantity',
    'color',
    'size',
    'variant',
    'declared_price',
    'declared_currency',
    'quoted_amount',
    'comment',
    'position',
])]
class PurchaseItem extends Model
{
    /** @use HasFactory<PurchaseItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marketplace' => Marketplace::class,
            'quantity' => 'integer',
            'declared_price' => 'decimal:2',
            'quoted_amount' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
