<?php

namespace App\Policies;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;

/**
 * Purchase requests are back-office data: only administrators may see or act on
 * them. Customers never reach these endpoints.
 */
class PurchaseRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user may move a request along its lifecycle.
     */
    public function updateStatus(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->isAdmin() && ! $purchaseRequest->status->isFinal();
    }

    public function sendQuote(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->isAdmin()
            && $purchaseRequest->status->canTransitionTo(PurchaseRequestStatus::QuoteSent);
    }

    /**
     * Whether money may be recorded against the request.
     *
     * Requires a quote, since there is nothing to settle before one, and stops
     * at cancellation. Everything in between stays open on purpose: customers
     * routinely pay a deposit up front and the balance once the parcel has
     * shipped, so payments are not confined to the "quote sent" step.
     */
    public function recordPayment(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->isAdmin()
            && $purchaseRequest->isQuoted()
            && $purchaseRequest->status !== PurchaseRequestStatus::Cancelled;
    }

    public function addNote(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->isAdmin();
    }

    public function viewAttachments(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->isAdmin();
    }
}
