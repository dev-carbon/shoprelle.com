<?php

namespace App\Models;

use App\Chatbot\Channel;
use App\Enums\PurchaseRequestStatus;
use App\Notifications\Contracts\RoutesMail;
use App\Notifications\Contracts\RoutesTelegram;
use Database\Factories\PurchaseRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A customer's request for Shoprelle to buy one or more products abroad.
 *
 * @property int $id
 * @property string $reference
 * @property int $customer_id
 * @property PurchaseRequestStatus $status
 * @property string $country
 * @property string $city
 * @property string $channel
 * @property string|null $channel_identifier
 * @property string|null $customer_comment
 * @property string|null $quote_items_amount
 * @property string|null $quote_shipping_amount
 * @property string|null $quote_total_amount
 * @property string|null $quote_currency
 * @property string|null $quote_cost_amount
 * @property string|null $quote_cost_currency
 * @property string|null $quote_exchange_rate
 * @property string|null $quote_notes
 * @property Carbon|null $quote_sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Customer $customer
 * @property-read Collection<int, PurchaseItem> $items
 * @property-read Collection<int, Attachment> $attachments
 * @property-read Collection<int, StatusHistory> $statusHistories
 * @property-read Collection<int, AdminNote> $adminNotes
 * @property-read Collection<int, Payment> $payments
 */
#[Fillable([
    'reference',
    'customer_id',
    'status',
    'country',
    'city',
    'channel',
    'channel_identifier',
    'customer_comment',
    'quote_items_amount',
    'quote_shipping_amount',
    'quote_total_amount',
    'quote_currency',
    'quote_cost_amount',
    'quote_cost_currency',
    'quote_exchange_rate',
    'quote_notes',
    'quote_sent_at',
])]
class PurchaseRequest extends Model implements RoutesMail, RoutesTelegram
{
    /** @use HasFactory<PurchaseRequestFactory> */
    use HasFactory;

    // The request, not the customer, is what gets notified on a messaging
    // channel: the conversation that produced it is the only thread we know how
    // to answer, and a customer may well have reached us through several.
    use RoutesNotifications;

    /**
     * Requests are addressed by their customer-facing reference, never by id.
     */
    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * The Telegram chat this request was written in, when it was written in one.
     */
    public function routeNotificationForTelegram(): ?string
    {
        return $this->channel === Channel::Telegram->value
            ? $this->channel_identifier
            : null;
    }

    /**
     * The address the customer left, which the assistant only ever offers.
     *
     * Blank rather than null is treated as absent: an empty string would be
     * accepted as a recipient and the message would go nowhere.
     */
    public function routeNotificationForMail(): ?string
    {
        $email = trim((string) $this->customer->email);

        return $email === '' ? null : $email;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseRequestStatus::class,
            'quote_items_amount' => 'decimal:2',
            'quote_shipping_amount' => 'decimal:2',
            'quote_total_amount' => 'decimal:2',
            'quote_cost_amount' => 'decimal:2',
            'quote_exchange_rate' => 'decimal:6',
            'quote_sent_at' => 'datetime',
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
     * @return HasMany<PurchaseItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @return HasMany<StatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->latest();
    }

    /**
     * @return HasMany<AdminNote, $this>
     */
    public function adminNotes(): HasMany
    {
        return $this->hasMany(AdminNote::class)->latest();
    }

    /**
     * Generate a short, readable and collision-resistant reference.
     *
     * The format is SHP-YYMM-XXXXXX. Uniqueness is enforced by the database, so
     * the loop only guards against the rare in-flight duplicate.
     */
    public static function generateReference(): string
    {
        $prefix = config('shoprelle.requests.reference_prefix');
        $period = now()->format('ym');

        do {
            $reference = sprintf('%s-%s-%s', $prefix, $period, Str::upper(Str::random(6)));
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('received_at');
    }

    public function isQuoted(): bool
    {
        return $this->quote_sent_at !== null;
    }

    /**
     * Everything received so far, instalments and refunds together.
     *
     * Sums the loaded relation rather than querying, so a screen that already
     * lists the payments does not pay for a second round trip to total them.
     */
    public function totalPaid(): string
    {
        $total = $this->payments->sum(fn (Payment $payment): float => (float) $payment->amount);

        return number_format($total, 2, '.', '');
    }

    /**
     * What the customer still owes. Negative once they have overpaid, which is
     * a real case: mobile money fees are sometimes added on the sender's side.
     */
    public function balance(): ?string
    {
        if ($this->quote_total_amount === null) {
            return null;
        }

        return number_format(
            (float) $this->quote_total_amount - (float) $this->totalPaid(),
            2,
            '.',
            '',
        );
    }

    /**
     * Whether the quote is fully covered, and therefore whether the purchase
     * can be made.
     */
    public function isSettled(): bool
    {
        $balance = $this->balance();

        return $balance !== null && (float) $balance <= 0.0;
    }

    /**
     * What the request earns, once the goods bought abroad are converted into
     * the currency the customer was billed in.
     *
     * Null unless the cost and the rate were both recorded on the quote, since
     * guessing either would produce a confidently wrong figure.
     */
    public function marginAmount(): ?string
    {
        if ($this->quote_total_amount === null
            || $this->quote_cost_amount === null
            || $this->quote_exchange_rate === null) {
            return null;
        }

        $cost = (float) $this->quote_cost_amount * (float) $this->quote_exchange_rate;

        return number_format((float) $this->quote_total_amount - $cost, 2, '.', '');
    }

    /**
     * The configured country name, falling back to the raw ISO code.
     */
    public function countryName(): string
    {
        return config('shoprelle.countries.'.$this->country, $this->country);
    }

    /**
     * Restrict a query to requests matching a free-text term on the reference,
     * the customer identity or a submitted product.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('reference', 'like', $like)
                ->orWhereHas('customer', function (Builder $query) use ($like): void {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })
                ->orWhereHas('items', function (Builder $query) use ($like): void {
                    $query->where('product_url', 'like', $like)
                        ->orWhere('product_name', 'like', $like);
                });
        });
    }
}
