<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Tells the back office that a customer submitted a new request.
 *
 * Delivery is limited to the in-app inbox for now. Adding 'mail', 'vonage' or a
 * custom WhatsApp channel to via() is all that is needed to widen it.
 */
class PurchaseRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
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
            'type' => 'purchase_request.submitted',
            'reference' => $this->purchaseRequest->reference,
            'customer_name' => $this->purchaseRequest->customer->full_name,
            'item_count' => $this->purchaseRequest->items->count(),
            'message' => sprintf(
                'Nouvelle demande %s de %s (%d produit%s).',
                $this->purchaseRequest->reference,
                $this->purchaseRequest->customer->full_name,
                $this->purchaseRequest->items->count(),
                $this->purchaseRequest->items->count() > 1 ? 's' : '',
            ),
        ];
    }
}
