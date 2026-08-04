<?php

namespace App\Chatbot;

/**
 * What the customer picked on the welcome menu.
 */
enum Intent: string
{
    case NewOrder = 'new_order';
    case TrackOrder = 'track_order';
    case MyOrders = 'my_orders';
    case LeaveReview = 'leave_review';
    case ContactUs = 'contact_us';
    case Help = 'help';

    public function label(): string
    {
        return match ($this) {
            self::NewOrder => '🛒 Nouvelle demande',
            self::TrackOrder => '📦 Suivre ma demande',
            self::MyOrders => '📋 Mes demandes',
            self::LeaveReview => '⭐ Laisser un avis',
            self::ContactUs => '✍️ Nous écrire',
            self::Help => '❓ Aide',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
