<?php

namespace App\Services;

use App\DataTransferObjects\ReviewData;
use App\Models\Review;
use App\Repositories\Contracts\CustomerRepository;
use App\Repositories\Contracts\PurchaseRequestRepository;

/**
 * Records what a customer thought.
 *
 * Attribution is best-effort and never blocking. A visitor can be halfway
 * through their first conversation, or have come back only to complain, and
 * either way the review is worth keeping — so a phone number nobody recognises
 * and a reference that matches nothing both leave the review anonymous rather
 * than refusing it.
 */
class ReviewService
{
    public function __construct(
        private CustomerRepository $customers,
        private PurchaseRequestRepository $requests,
    ) {}

    public function record(ReviewData $review): Review
    {
        $customer = $review->phone === null || $review->phone === ''
            ? null
            : $this->customers->findByPhone($review->phone);

        return Review::create([
            ...$review->toAttributes(),
            'customer_id' => $customer?->id,
            'purchase_request_id' => $this->requestId($review, $customer?->id),
        ]);
    }

    /**
     * The request a review is about, when the conversation happens to know it.
     *
     * The reference is only accepted when it belongs to the customer the phone
     * number resolved to. Without that check, anybody could pin a one-star
     * review onto a stranger's order by quoting a reference they saw once.
     */
    private function requestId(ReviewData $review, ?int $customerId): ?int
    {
        if ($review->reference === null || $customerId === null) {
            return null;
        }

        $request = $this->requests->findByReference($review->reference);

        return $request?->customer_id === $customerId ? $request->id : null;
    }
}
