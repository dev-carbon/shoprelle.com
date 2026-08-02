<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use Database\Factories\StatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An audit entry recording one status change of a purchase request.
 *
 * @property int $id
 * @property int $purchase_request_id
 * @property PurchaseRequestStatus|null $from_status
 * @property PurchaseRequestStatus $to_status
 * @property int|null $user_id
 * @property string|null $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseRequest $purchaseRequest
 * @property-read User|null $user
 */
#[Fillable(['purchase_request_id', 'from_status', 'to_status', 'user_id', 'comment'])]
class StatusHistory extends Model
{
    /** @use HasFactory<StatusHistoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => PurchaseRequestStatus::class,
            'to_status' => PurchaseRequestStatus::class,
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
