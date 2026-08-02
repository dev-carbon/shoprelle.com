<?php

namespace App\Models;

use Database\Factories\AdminNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A back-office note attached to a purchase request. Internal by definition and
 * never returned by a customer-facing endpoint.
 *
 * @property int $id
 * @property int $purchase_request_id
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseRequest $purchaseRequest
 * @property-read User $user
 */
#[Fillable(['purchase_request_id', 'user_id', 'body'])]
class AdminNote extends Model
{
    /** @use HasFactory<AdminNoteFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
