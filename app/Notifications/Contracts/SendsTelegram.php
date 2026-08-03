<?php

namespace App\Notifications\Contracts;

/**
 * A notification that knows how to word itself for Telegram.
 */
interface SendsTelegram
{
    public function toTelegram(object $notifiable): string;
}
