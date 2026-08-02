<?php

namespace App\Enums;

/**
 * How a customer settled a quote.
 *
 * The cases stay operator-agnostic on purpose: the launch market pays by mobile
 * money, but which wallet varies by country, so the operator is recorded in the
 * payment's `provider` column rather than multiplied into cases here. That also
 * means the enum survives opening a new country.
 */
enum PaymentMethod: string
{
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Card = 'card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile money',
            self::BankTransfer => 'Virement bancaire',
            self::Cash => 'Espèces',
            self::Card => 'Carte bancaire',
            self::Other => 'Autre',
        };
    }

    /**
     * Whether the method carries a transaction identifier the customer can quote
     * back. Cash and "other" have nothing to reconcile against, so the reference
     * field is only meaningful for the rest.
     */
    public function hasProviderReference(): bool
    {
        return match ($this) {
            self::MobileMoney, self::BankTransfer, self::Card => true,
            self::Cash, self::Other => false,
        };
    }

    /**
     * All cases as select options for the frontend.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases(),
        );
    }
}
