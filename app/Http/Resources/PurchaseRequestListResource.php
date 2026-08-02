<?php

namespace App\Http\Resources;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the admin request table.
 *
 * @mixin PurchaseRequest
 */
class PurchaseRequestListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'customer_name' => $this->customer->full_name,
            'customer_phone' => $this->customer->phone,
            'country' => $this->country,
            'country_label' => $this->countryName(),
            'city' => $this->city,
            'marketplaces' => $this->items
                ->map(fn ($item): string => $item->marketplace->label())
                ->unique()
                ->values()
                ->all(),
            'item_count' => $this->items_count ?? $this->items->count(),
            'total_quantity' => $this->items->sum('quantity'),
            'quote_total_amount' => $this->quote_total_amount,
            'quote_currency' => $this->quote_currency,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
