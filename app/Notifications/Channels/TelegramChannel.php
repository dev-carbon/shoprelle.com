<?php

namespace App\Notifications\Channels;

use App\Chatbot\Channels\Telegram\TelegramClient;
use App\Notifications\Contracts\RoutesTelegram;
use App\Notifications\Contracts\SendsTelegram;
use Illuminate\Notifications\Notification;

/**
 * Delivers a notification into the Telegram conversation it belongs to.
 *
 * The notifiable says which chat to post in, the notification says what to
 * write. A failure to send is logged by the client and swallowed here: a
 * customer we cannot reach must not undo the action that was already recorded.
 */
class TelegramChannel
{
    public function __construct(
        private TelegramClient $client,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof RoutesTelegram || ! $notification instanceof SendsTelegram) {
            return;
        }

        $chatId = $notifiable->routeNotificationForTelegram();

        if (! is_numeric($chatId)) {
            return;
        }

        $text = trim($notification->toTelegram($notifiable));

        if ($text === '') {
            return;
        }

        $this->client->sendMessage((int) $chatId, $text);
    }
}
