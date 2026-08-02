<?php

namespace App\Http\Resources;

use App\Models\Customer;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A customer with their request history.
 *
 * @mixin Customer
 */
class CustomerDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $requests = $this->purchaseRequests;

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'country' => $this->country,
            'country_label' => $this->countryName(),
            'city' => $this->city,
            'created_at' => $this->created_at?->toIso8601String(),

            // Whether one exists, never the code itself: it is hashed, and the
            // screen only needs to know if this customer can be helped.
            'has_access_code' => $this->access_code_hash !== null,

            'summary' => [
                'request_count' => $requests->count(),
                'active_count' => $requests->reject(
                    fn (PurchaseRequest $purchaseRequest): bool => $purchaseRequest->status->isFinal(),
                )->count(),
                'quoted_total' => number_format($requests->sum(
                    fn (PurchaseRequest $purchaseRequest): float => (float) $purchaseRequest->quote_total_amount,
                ), 2, '.', ''),
                'quote_currency' => $requests->pluck('quote_currency')->filter()->first()
                    ?? config('shoprelle.quote_currency'),
            ],

            'requests' => $requests->map(fn (PurchaseRequest $purchaseRequest): array => [
                'reference' => $purchaseRequest->reference,
                'status' => $purchaseRequest->status->value,
                'status_label' => $purchaseRequest->status->label(),
                'status_color' => $purchaseRequest->status->color(),
                'item_count' => (int) ($purchaseRequest->items_count ?? 0),
                'city' => $purchaseRequest->city,
                'quote_total_amount' => $purchaseRequest->quote_total_amount,
                'quote_currency' => $purchaseRequest->quote_currency,
                'created_at' => $purchaseRequest->created_at?->toIso8601String(),
            ])->all(),
        ];
    }
}
