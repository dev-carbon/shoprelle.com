<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifier;

/**
 * The single place that decides who gets told about what.
 *
 * Administrators are told in-app; customers are told over the channel their
 * request came from, which today means Telegram and nothing else. Adding email,
 * SMS or WhatsApp is a matter of adding a channel to the notification's via()
 * method: no caller changes.
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

    /**
     * Tell the customer, in the conversation their request came from.
     *
     * The request is the notifiable rather than the customer: it is what knows
     * which thread to answer. A request that came in over a channel we cannot
     * answer simply notifies nobody, which the notification decides on its own.
     */
    public function notifyCustomer(PurchaseRequest $request, Notification $notification): void
    {
        $request->notify($notification);
    }
}
