<?php

namespace App\Http\Resources;

use App\Enums\PurchaseRequestStatus;
use App\Models\Payment;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Services\PaymentWallets;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One request as its own customer reads it.
 *
 * Deliberately not the admin resource with fields removed: this one is written
 * from nothing, so that a field added to the back office tomorrow cannot leak
 * here by accident. What must never appear is the purchase cost, the exchange
 * rate, the margin and the internal notes — the whole of the back office's own
 * arithmetic.
 *
 * @mixin PurchaseRequest
 */
class CustomerRequestResource extends JsonResource
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
            // Ce que le client peut encore faire de son devis. Décidé ici, à
            // partir du cycle de vie lui-même, plutôt que redéduit dans l'écran
            // à partir d'un statut — les deux auraient fini par diverger.
            'awaits_decision' => $this->status === PurchaseRequestStatus::QuoteSent,
            'created_at' => $this->created_at?->toIso8601String(),
            'destination' => trim($this->city.', '.$this->countryName(), ', '),

            'items' => $this->items->map(fn (PurchaseItem $item, int $index): array => [
                'id' => $item->id,
                'name' => $item->product_name ?? 'Produit n°'.($index + 1),
                'marketplace_label' => $item->marketplace->label(),
                'product_url' => $item->product_url,
                'quantity' => $item->quantity,
                'color' => $item->color,
                'size' => $item->size,
                // What this line was quoted at, null while the request is
                // still being priced.
                'quoted_amount' => $item->quoted_amount,
            ])->all(),

            'quote' => $this->isQuoted() ? [
                'items_amount' => $this->quote_items_amount,
                'shipping_amount' => $this->quote_shipping_amount,
                'total_amount' => $this->quote_total_amount,
                'currency' => $this->quote_currency,
                'notes' => $this->quote_notes,
                'sent_at' => $this->quote_sent_at?->toIso8601String(),
            ] : null,

            // Où envoyer l'argent, une fois le devis accepté et tant qu'il reste
            // quelque chose à régler. Avant l'acceptation il n'y a rien à payer,
            // et un numéro affiché trop tôt se lit comme une facture.
            'payment_instructions' => $this->awaitsPayment() ? [
                'wallets' => PaymentWallets::payable(),
                'account_name' => PaymentWallets::accountName(),
                'amount' => $this->balance(),
                'currency' => $this->quote_currency,
            ] : null,

            'payments' => $this->isQuoted() ? [
                'currency' => $this->quote_currency,
                'total_paid' => $this->totalPaid(),
                'balance' => $this->balance(),
                'is_settled' => $this->isSettled(),
                'entries' => $this->payments->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'method_label' => $payment->method->label(),
                    'received_at' => $payment->received_at->toIso8601String(),
                ])->all(),
            ] : null,
        ];
    }
}
