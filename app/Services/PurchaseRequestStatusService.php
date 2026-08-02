<?php

namespace App\Services;

use App\DataTransferObjects\QuoteData;
use App\Enums\PurchaseRequestStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PurchaseRequestStatusChanged;
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
        });

        return $this->transition(
            $request,
            PurchaseRequestStatus::QuoteSent,
            $user,
            sprintf('Devis de %s %s envoyé.', $quote->totalAmount(), $quote->currency),
        );
    }
}
