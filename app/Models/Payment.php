<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Money received against a purchase request.
 *
 * Deliberately one row per instalment rather than a column on the request:
 * customers commonly pay a deposit to trigger the purchase and the balance on
 * arrival, and a cancellation after payment has to be recorded as a negative
 * line rather than by overwriting what was received.
 *
 * Today rows are keyed in by an administrator reconciling a mobile money
 * statement. When a payment provider is integrated, its webhook writes the same
 * row and nothing else in the domain has to change.
 *
 * @property int $id
 * @property int $purchase_request_id
 * @property PaymentMethod $method
 * @property string|null $provider
 * @property string $amount
 * @property string $currency
 * @property string|null $provider_reference
 * @property Carbon $received_at
 * @property int|null $recorded_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseRequest $purchaseRequest
 * @property-read User|null $recordedBy
 */
#[Fillable([
    'purchase_request_id',
    'method',
    'provider',
    'amount',
    'currency',
    'provider_reference',
    'received_at',
    'recorded_by',
    'notes',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'received_at' => 'datetime',
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
     * The administrator who keyed the payment in, absent once a provider
     * webhook records them or when the employee's account has been deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
