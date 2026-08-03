<?php

namespace App\Notifications\Contracts;

/**
 * Something that can be written back to on Telegram.
 */
interface RoutesTelegram
{
    /**
     * The chat to post in, or null when there is none to post in.
     */
    public function routeNotificationForTelegram(): ?string;
}
