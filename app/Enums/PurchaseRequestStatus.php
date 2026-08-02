<?php

namespace App\Enums;

/**
 * Lifecycle of a purchase request, from customer submission to delivery.
 *
 * Transitions are declared here rather than in a service so that the rules stay
 * next to the states they govern and remain the single source of truth for the
 * API, the admin UI and the tests.
 */
enum PurchaseRequestStatus: string
{
    case New = 'new';
    case Pending = 'pending';
    case QuoteSent = 'quote_sent';
    case PaymentReceived = 'payment_received';
    case Purchased = 'purchased';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * The human readable name shown to customers and administrators.
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Pending => 'En attente',
            self::QuoteSent => 'Devis envoyé',
            self::PaymentReceived => 'Paiement reçu',
            self::Purchased => 'Achat effectué',
            self::Preparing => 'En préparation',
            self::Shipped => 'Expédié',
            self::Delivered => 'Livré',
            self::Cancelled => 'Annulé',
        };
    }

    /**
     * Semantic colour token consumed by the frontend badge component.
     *
     * The scale reads as a journey: blue while Shoprelle is still deciding,
     * amber while waiting on the customer, green once money has moved, violet
     * in transit, deep green on arrival, red when it ends early.
     */
    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            // Both mean "waiting on the customer", so they share a hue.
            self::Pending, self::QuoteSent => 'amber',
            self::PaymentReceived => 'green',
            // Two internal handling stages, indistinguishable to the customer.
            self::Purchased, self::Preparing => 'cyan',
            self::Shipped => 'violet',
            self::Delivered => 'emerald',
            self::Cancelled => 'red',
        };
    }

    /**
     * Statuses this one may move to.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Pending, self::QuoteSent, self::Cancelled],
            self::Pending => [self::QuoteSent, self::Cancelled],
            self::QuoteSent => [self::PaymentReceived, self::Pending, self::Cancelled],
            self::PaymentReceived => [self::Purchased, self::Cancelled],
            self::Purchased => [self::Preparing, self::Cancelled],
            self::Preparing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), strict: true);
    }

    /**
     * A closed request no longer moves and is excluded from the "active" counters.
     */
    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * All cases as select options for the frontend.
     *
     * @return list<array{value: string, label: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ],
            self::cases(),
        );
    }
}
