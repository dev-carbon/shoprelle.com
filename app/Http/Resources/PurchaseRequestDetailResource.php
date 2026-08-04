<?php

namespace App\Http\Resources;

use App\Chatbot\Channel;
use App\Models\Attachment;
use App\Models\Payment;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full request as shown on the admin detail screen.
 *
 * @mixin PurchaseRequest
 */
class PurchaseRequestDetailResource extends JsonResource
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
            'channel' => $this->channel,
            'channel_label' => Channel::tryFrom($this->channel)?->label() ?? $this->channel,
            // By what means sending a quote actually reaches this customer.
            // Empty when it reaches nobody, and an administrator has to carry
            // it by hand — which the screen then says plainly.
            // Worded with their preposition rather than named: the screen joins
            // them into one sentence, and "sur Telegram" and "par email" do not
            // take the same one.
            'delivery_channels' => array_values(array_filter([
                $this->routeNotificationForTelegram() !== null ? 'sur Telegram' : null,
                $this->routeNotificationForMail() !== null ? 'par email' : null,
            ])),
            'customer_comment' => $this->customer_comment,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'customer' => [
                'first_name' => $this->customer->first_name,
                'last_name' => $this->customer->last_name,
                'full_name' => $this->customer->full_name,
                'phone' => $this->customer->phone,
                'email' => $this->customer->email,
            ],

            'destination' => [
                'country' => $this->country,
                'country_label' => $this->countryName(),
                'city' => $this->city,
            ],

            'quote' => $this->isQuoted() ? [
                'items_amount' => $this->quote_items_amount,
                'shipping_amount' => $this->quote_shipping_amount,
                'total_amount' => $this->quote_total_amount,
                'currency' => $this->quote_currency,
                'cost_amount' => $this->quote_cost_amount,
                'cost_currency' => $this->quote_cost_currency,
                'exchange_rate' => $this->quote_exchange_rate,
                'margin_amount' => $this->marginAmount(),
                'notes' => $this->quote_notes,
                'sent_at' => $this->quote_sent_at?->toIso8601String(),
            ] : null,

            // Null before a quote exists: there is nothing to settle, and the
            // screen hides the whole card rather than showing an empty ledger.
            'payments' => $this->isQuoted() ? [
                'currency' => $this->quote_currency,
                'total_paid' => $this->totalPaid(),
                'balance' => $this->balance(),
                'is_settled' => $this->isSettled(),
                'entries' => $this->payments->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'method' => $payment->method->value,
                    'method_label' => $payment->method->label(),
                    'provider' => $payment->provider,
                    'provider_reference' => $payment->provider_reference,
                    'received_at' => $payment->received_at->toIso8601String(),
                    'recorded_by' => $payment->recordedBy?->name,
                    'notes' => $payment->notes,
                ])->all(),
            ] : null,

            'items' => $this->items->map(fn (PurchaseItem $item): array => [
                'id' => $item->id,
                'marketplace' => $item->marketplace->value,
                'marketplace_label' => $item->marketplace->label(),
                'product_url' => $item->product_url,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'color' => $item->color,
                'size' => $item->size,
                'variant' => $item->variant,
                'declared_price' => $item->declared_price,
                'declared_currency' => $item->declared_currency,
                'quoted_amount' => $item->quoted_amount,
                'comment' => $item->comment,
                'attachments' => $item->attachments->map($this->attachment(...))->all(),
            ])->all(),

            'status_history' => $this->statusHistories->map(fn ($entry): array => [
                'id' => $entry->id,
                'from' => $entry->from_status?->value,
                'from_label' => $entry->from_status?->label(),
                'to' => $entry->to_status->value,
                'to_label' => $entry->to_status->label(),
                'to_color' => $entry->to_status->color(),
                'author' => $entry->user?->name,
                'comment' => $entry->comment,
                'created_at' => $entry->created_at?->toIso8601String(),
            ])->all(),

            'notes' => $this->adminNotes->map(fn ($note): array => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->user->name,
                'created_at' => $note->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attachment(Attachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'size' => $attachment->size,
            'mime_type' => $attachment->mime_type,
            'url' => route('admin.attachments.show', [
                'purchaseRequest' => $this->reference,
                'attachment' => $attachment->id,
            ]),
        ];
    }
}
