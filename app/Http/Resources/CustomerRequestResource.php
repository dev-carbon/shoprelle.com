<?php

namespace App\Http\Resources;

use App\Enums\PurchaseRequestStatus;
use App\Models\Attachment;
use App\Models\Payment;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\StatusHistory;
use App\Services\AcceptedPayments;
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

            /*
             * ── Le chemin parcouru, pas seulement l'étape du moment ─────────
             *
             * La vitrine promet « votre référence vous dit où il en est tout du
             * long » ; cette page ne montrait que le statut courant, ce qui
             * n'est pas « tout du long ». L'historique existait déjà en base,
             * rempli à chaque changement, et n'était lu que par le back-office.
             *
             * Ce qui n'en sort pas : le nom de qui a changé le statut, et les
             * commentaires internes. Le client a le droit de savoir où en est
             * sa demande, pas de lire les notes de service.
             */
            'timeline' => $this->statusHistories->map(fn (StatusHistory $entry): array => [
                'id' => $entry->id,
                'label' => $entry->to_status->label(),
                'color' => $entry->to_status->color(),
                'at' => $entry->created_at?->toIso8601String(),
            ])->all(),

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
                /*
                 * Les captures que le client a jointes à ce produit. Seulement
                 * de quoi les afficher : le chemin sur le disque et le nom du
                 * fichier d'origine restent au back-office.
                 */
                'attachments' => $item->attachments->map(fn (Attachment $attachment): array => [
                    'id' => $attachment->id,
                    'url' => route('orders.attachments.show', [
                        'reference' => $this->reference,
                        'attachment' => $attachment->id,
                    ]),
                ])->all(),
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
                'methods' => AcceptedPayments::payable(),
                'account_name' => AcceptedPayments::accountName(),
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
