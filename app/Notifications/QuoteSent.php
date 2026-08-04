<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Contracts\RoutesMail;
use App\Notifications\Contracts\RoutesTelegram;
use App\Notifications\Contracts\SendsTelegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Hands the customer the quote, wherever we know how to reach them.
 *
 * Telegram answers the conversation the request came from; email goes to the
 * address they left, which is optional and often absent. Both may fire at once,
 * and neither firing is a normal outcome rather than a failure: a web customer
 * who gave no address is carried by hand, and the back office says so.
 *
 * The figures are built once in `lines()` and worded per channel, so the two
 * messages can never come to disagree about what was quoted.
 */
class QuoteSent extends Notification implements SendsTelegram, ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable instanceof RoutesTelegram && $notifiable->routeNotificationForTelegram() !== null) {
            $channels[] = TelegramChannel::class;
        }

        if ($notifiable instanceof RoutesMail && $notifiable->routeNotificationForMail() !== null) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toTelegram(object $notifiable): string
    {
        return $this->asPlainText();
    }

    /**
     * The quote as the customer reads it: every line, then what it adds up to.
     *
     * Public because it is also what an administrator hands over by hand, from
     * the back office, when no channel can carry it. One wording, whoever
     * delivers it — a message retyped from memory each time is a message that
     * ends up promising something the quote does not say.
     */
    public function asPlainText(): string
    {
        $request = $this->request();

        $message = sprintf(
            "Bonjour %s, voici le devis de votre demande %s.\n\n%s\n\nProduits : %s\nLivraison : %s\nTotal : %s",
            $request->customer->first_name,
            $request->reference,
            implode("\n", array_map(fn (string $line): string => '• '.$line, $this->lines())),
            $this->money($request->quote_items_amount),
            $this->money($request->quote_shipping_amount),
            $this->money($request->quote_total_amount),
        );

        if ($request->quote_notes !== null) {
            $message .= "\n\n".$request->quote_notes;
        }

        return $message."\n\nRépondez à ce message si vous avez une question.";
    }

    /**
     * The same quote, for the address the customer left us.
     *
     * The mail carries the figures in full rather than a link to them: an email
     * that says only "you have a quote" is an email that has to be trusted
     * before it is useful, and this one is often read on a slow connection.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->request();

        $mail = (new MailMessage)
            ->subject(sprintf('Votre devis Shoprelle — %s', $request->reference))
            ->greeting(sprintf('Bonjour %s,', $request->customer->first_name))
            ->line(sprintf('Voici le devis de votre demande %s.', $request->reference));

        foreach ($this->lines() as $line) {
            $mail->line($line);
        }

        $mail->line(sprintf(
            '**Produits : %s — Livraison : %s — Total : %s**',
            $this->money($request->quote_items_amount),
            $this->money($request->quote_shipping_amount),
            $this->money($request->quote_total_amount),
        ));

        if ($request->quote_notes !== null) {
            $mail->line($request->quote_notes);
        }

        return $mail
            ->action('Voir mes demandes', route('orders.index'))
            // Said here because the page will ask for it and the code cannot be
            // resent: it is stored hashed, and nobody can read it back.
            ->line("L'accès demande votre numéro de téléphone et le code reçu lors de votre première demande.")
            ->salutation('À très vite, l\'équipe Shoprelle');
    }

    /**
     * One line per product: what it is, how many, and what it is billed at.
     *
     * @return list<string>
     */
    private function lines(): array
    {
        $lines = [];
        $position = 0;

        foreach ($this->request()->items as $item) {
            $position++;

            $lines[] = sprintf(
                '%s (×%d) : %s',
                $item->product_name ?? 'Produit n°'.$position,
                $item->quantity,
                $this->money($item->quoted_amount),
            );
        }

        return $lines;
    }

    /**
     * The request with everything the message reads, loaded once.
     */
    private function request(): PurchaseRequest
    {
        return $this->purchaseRequest->loadMissing(['customer', 'items']);
    }

    /**
     * An amount as it is read aloud: grouped thousands, no trailing cents on a
     * round figure, and the currency spelled out.
     */
    private function money(?string $amount): string
    {
        $value = (float) $amount;
        $decimals = fmod($value, 1.0) === 0.0 ? 0 : 2;

        return number_format($value, $decimals, ',', ' ').' '.$this->purchaseRequest->quote_currency;
    }
}
