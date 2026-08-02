<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A customer-supplied screenshot backing a purchase item.
 *
 * Files live on a private disk; they are only ever served through an authorized
 * controller action, never by public URL.
 *
 * @property int $id
 * @property int $purchase_request_id
 * @property int|null $purchase_item_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseRequest $purchaseRequest
 * @property-read PurchaseItem|null $purchaseItem
 */
#[Fillable([
    'purchase_request_id',
    'purchase_item_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
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
     * @return BelongsTo<PurchaseItem, $this>
     */
    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function fileExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Delete the underlying file. Called when the record is discarded.
     */
    public function deleteFile(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}
