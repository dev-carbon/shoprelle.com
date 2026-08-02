<?php

namespace App\Notifications;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Tells the back office that a request moved to a new status.
 *
 * When a customer-facing channel is added, this is the notification that will
 * also reach the buyer, which is why it already carries the human labels.
 */
class PurchaseRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
        public PurchaseRequestStatus $from,
        public PurchaseRequestStatus $to,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'purchase_request.status_changed',
            'reference' => $this->purchaseRequest->reference,
            'from' => $this->from->value,
            'to' => $this->to->value,
            'message' => sprintf(
                'Demande %s : %s → %s.',
                $this->purchaseRequest->reference,
                $this->from->label(),
                $this->to->label(),
            ),
        ];
    }
}
