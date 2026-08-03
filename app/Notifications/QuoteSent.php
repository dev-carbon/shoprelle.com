<?php

namespace App\Notifications;

use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Contracts\RoutesTelegram;
use App\Notifications\Contracts\SendsTelegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Hands the customer the quote, in the conversation they wrote to us from.
 *
 * Only Telegram is delivered today. Email and WhatsApp join by adding their
 * channel to `via()`: the message is built here once, per channel, and the back
 * office keeps sending quotes exactly as before.
 */
class QuoteSent extends Notification implements SendsTelegram, ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
    ) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        // An unreachable customer is not an error: web requests have no thread
        // to answer, and their quote is carried by hand for now.
        return $notifiable instanceof RoutesTelegram
            && $notifiable->routeNotificationForTelegram() !== null
                ? [TelegramChannel::class]
                : [];
    }

    /**
     * The quote as the customer reads it: every line, then what it adds up to.
     *
     * The purchase cost and the exchange rate are deliberately absent — they are
     * back-office figures, and this message is the one thing the customer sees.
     */
    public function toTelegram(object $notifiable): string
    {
        $request = $this->purchaseRequest->loadMissing(['customer', 'items']);
        $currency = (string) $request->quote_currency;

        $lines = $request->items
            ->map(fn (PurchaseItem $item, int $index): string => sprintf(
                '• %s (×%d) : %s',
                $item->product_name ?? 'Produit n°'.($index + 1),
                $item->quantity,
                $this->money($item->quoted_amount, $currency),
            ))
            ->implode("\n");

        $message = sprintf(
            "Bonjour %s, voici le devis de votre demande %s.\n\n%s\n\nProduits : %s\nLivraison : %s\nTotal : %s",
            $request->customer->first_name,
            $request->reference,
            $lines,
            $this->money($request->quote_items_amount, $currency),
            $this->money($request->quote_shipping_amount, $currency),
            $this->money($request->quote_total_amount, $currency),
        );

        if ($request->quote_notes !== null) {
            $message .= "\n\n".$request->quote_notes;
        }

        return $message."\n\nRépondez à ce message si vous avez une question.";
    }

    /**
     * An amount as it is read aloud: grouped thousands, no trailing cents on a
     * round figure, and the currency spelled out.
     */
    private function money(?string $amount, string $currency): string
    {
        $value = (float) $amount;
        $decimals = fmod($value, 1.0) === 0.0 ? 0 : 2;

        return number_format($value, $decimals, ',', ' ').' '.$currency;
    }
}
