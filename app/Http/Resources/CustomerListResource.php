<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the admin customer table.
 *
 * @mixin Customer
 */
class CustomerListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'country' => $this->country,
            'country_label' => $this->countryName(),
            'city' => $this->city,
            'request_count' => (int) ($this->purchase_requests_count ?? 0),
            'last_request_at' => $this->purchase_requests_max_created_at,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
