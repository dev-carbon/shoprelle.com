<?php

namespace App\Notifications\Contracts;

/**
 * Something that may carry an email address to write to.
 */
interface RoutesMail
{
    /**
     * The address to write to, or null when there is none.
     */
    public function routeNotificationForMail(): ?string;
}
