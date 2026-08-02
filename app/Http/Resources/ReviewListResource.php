<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the admin review list.
 *
 * @mixin Review
 */
class ReviewListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'is_approved' => $this->isApproved(),
            'created_at' => $this->created_at?->toIso8601String(),
            // Null whenever the reviewer was never identified, which is the
            // normal case: no account is needed to talk to the assistant.
            'customer' => $this->whenLoaded('customer', fn (): ?array => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'full_name' => $this->customer->full_name,
            ]),
            'reference' => $this->whenLoaded(
                'purchaseRequest',
                fn (): ?string => $this->purchaseRequest?->reference,
            ),
        ];
    }
}
