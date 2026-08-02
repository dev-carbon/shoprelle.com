<?php

namespace App\Services;

use App\DataTransferObjects\PaymentData;
use App\Enums\PurchaseRequestStatus;
use App\Models\Payment;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Owns the recording of money received against a purchase request.
 *
 * Everything here is deliberately provider-agnostic. Payments are keyed in by
 * hand today, reconciled against a mobile money statement; the day an
 * aggregator is integrated its webhook builds the same PaymentData and calls
 * this same method, and nothing downstream notices the difference.
 *
 * Status is never written here: once the instalments cover the quote, the
 * transition is delegated to PurchaseRequestStatusService, which stays the only
 * writer of that column and the only thing that records history.
 */
class PaymentService
{
    public function __construct(
        private PurchaseRequestStatusService $statuses,
    ) {}

    /**
     * Record one instalment and settle the request if it is now fully paid.
     *
     * A partial payment leaves the status alone: the request stays at "quote
     * sent" until the balance reaches zero, which is what keeps an unpaid
     * balance from silently authorising the purchase.
     */
    public function record(PurchaseRequest $request, PaymentData $payment, ?User $user = null): Payment
    {
        $recorded = DB::transaction(fn (): Payment => $request->payments()->create([
            ...$payment->toAttributes(),
            'recorded_by' => $user?->id,
        ]));

        // The relation may already have been loaded by the screen that sent us
        // here, in which case it predates the row just written.
        $request->load('payments');

        if ($request->isSettled() && $request->status->canTransitionTo(PurchaseRequestStatus::PaymentReceived)) {
            $this->statuses->transition(
                $request,
                PurchaseRequestStatus::PaymentReceived,
                $user,
                sprintf(
                    'Paiement complet reçu : %s %s.',
                    $request->totalPaid(),
                    $request->quote_currency,
                ),
            );
        }

        return $recorded;
    }
}
