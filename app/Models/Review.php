<?php

namespace App\Models;

use App\Chatbot\Channel;
use Carbon\CarbonImmutable;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a customer thought, said to the assistant.
 *
 * Les dates sont immuables : `AppServiceProvider` appelle
 * `Date::use(CarbonImmutable::class)`, donc c'est bien une CarbonImmutable que
 * la conversion rend et que `now()` produit — annoncer une `Carbon` mutable
 * faisait échouer l'analyse à la seule ligne qui écrit dans la colonne.
 *
 * @property int $id
 * @property int|null $customer_id
 * @property int|null $purchase_request_id
 * @property int $rating
 * @property string|null $comment
 * @property Channel $channel
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $created_at
 */
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /** The lowest and highest a rating may be, in one place. */
    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => Channel::class,
            'rating' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Reviews cleared for anywhere a visitor can see.
     *
     * @param  Builder<Review>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->whereNotNull('approved_at');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * The rating drawn as stars, which is how both the back office and any
     * message channel end up showing it.
     */
    public function stars(): string
    {
        return str_repeat('★', $this->rating).str_repeat('☆', self::MAX_RATING - $this->rating);
    }
}
