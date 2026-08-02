<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifier;

/**
 * The single place that decides who gets told about what.
 *
 * Only in-app notifications are delivered today. Adding email, SMS, WhatsApp or
 * Telegram is a matter of adding a channel to the notification's via() method:
 * no caller changes.
 */
class NotificationService
{
    /**
     * Notify every back-office user, optionally skipping the one who triggered
     * the change so administrators are not notified of their own actions.
     */
    public function notifyAdministrators(Notification $notification, ?User $except = null): void
    {
        $administrators = User::query()
            ->where('is_admin', true)
            ->when($except, fn ($query, User $user) => $query->whereKeyNot($user->getKey()))
            ->get();

        if ($administrators->isEmpty()) {
            return;
        }

        Notifier::send($administrators, $notification);
    }
}
