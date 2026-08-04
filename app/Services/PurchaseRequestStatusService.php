<?php

namespace App\Services;

use App\DataTransferObjects\QuoteData;
use App\Enums\PurchaseRequestStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PurchaseRequestStatusChanged;
use App\Notifications\QuoteSent;
use Illuminate\Support\Facades\DB;

/**
 * Owns every status change of a purchase request.
 *
 * Nothing else may write to the status column: routing all changes through here
 * is what guarantees the history is complete and the transitions are legal.
 */
class PurchaseRequestStatusService
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    /**
     * Move a request to a new status, recording who did it and why.
     *
     * @throws InvalidStatusTransition when the lifecycle forbids the change
     */
    public function transition(
        PurchaseRequest $request,
        PurchaseRequestStatus $to,
        ?User $user = null,
        ?string $comment = null,
    ): PurchaseRequest {
        $from = $request->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidStatusTransition::between($from, $to);
        }

        DB::transaction(function () use ($request, $from, $to, $user, $comment): void {
            $request->forceFill(['status' => $to])->save();

            $request->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $user?->id,
                'comment' => $comment,
            ]);
        });

        $this->notifications->notifyAdministrators(
            new PurchaseRequestStatusChanged($request->fresh(['customer']), $from, $to),
            except: $user,
        );

        return $request;
    }

    /**
     * The customer says yes to the quote.
     *
     * No `User`: the author of this one is the customer, and the history says
     * so in words rather than by pointing at an administrator who did nothing.
     * The money has not moved yet — accepting is a promise, and the request
     * stays on the customer's side of the court until it is paid.
     *
     * @throws InvalidStatusTransition when the request is not awaiting an answer
     */
    public function acceptQuote(PurchaseRequest $request): PurchaseRequest
    {
        return $this->transition(
            $request,
            PurchaseRequestStatus::QuoteAccepted,
            comment: 'Devis accepté par le client.',
        );
    }

    /**
     * The customer says no.
     *
     * Back to "pending" rather than cancelled: a refused quote is almost always
     * a quote to redo, and cancelling would close a request the customer never
     * asked to end. The reason, when given, is the whole point — it is what
     * tells an administrator what to change.
     *
     * @throws InvalidStatusTransition when the request is not awaiting an answer
     */
    public function declineQuote(PurchaseRequest $request, ?string $reason = null): PurchaseRequest
    {
        $reason = $reason === null ? null : trim($reason);

        return $this->transition(
            $request,
            PurchaseRequestStatus::Pending,
            comment: $reason === null || $reason === ''
                ? 'Devis refusé par le client.'
                : 'Devis refusé par le client : '.$reason,
        );
    }

    /**
     * Record a quote and move the request to "quote sent" in one operation, so
     * a request can never carry amounts without the matching status.
     *
     * @throws InvalidStatusTransition
     */
    public function sendQuote(PurchaseRequest $request, QuoteData $quote, User $user): PurchaseRequest
    {
        if (! $request->status->canTransitionTo(PurchaseRequestStatus::QuoteSent)) {
            throw InvalidStatusTransition::between($request->status, PurchaseRequestStatus::QuoteSent);
        }

        DB::transaction(function () use ($request, $quote): void {
            $request->forceFill($quote->toAttributes())->save();

            // The lines and the total they add up to must never disagree, so
            // they are written together or not at all.
            foreach ($request->items as $item) {
                $item->update(['quoted_amount' => $quote->lineAmounts[$item->id] ?? null]);
            }
        });

        $request = $this->transition(
            $request,
            PurchaseRequestStatus::QuoteSent,
            $user,
            sprintf('Devis de %s %s envoyé.', $quote->totalAmount(), $quote->currency),
        );

        // Last, and outside the transaction: a customer we cannot reach must not
        // undo a quote that is already recorded and already visible in the back
        // office. Requests from a channel we cannot answer notify nobody.
        $this->notifications->notifyCustomer($request, new QuoteSent($request));

        return $request;
    }
}
