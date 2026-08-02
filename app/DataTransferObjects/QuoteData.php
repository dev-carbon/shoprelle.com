<?php

namespace App\DataTransferObjects;

/**
 * The figures an administrator sends to a customer for a purchase request.
 *
 * Shipping is quoted separately from the goods so that the automatic weight
 * based calculation can later replace only that part.
 *
 * The cost side is optional and never leaves the back office: it is what the
 * goods are expected to cost abroad, in the currency they are bought in, plus
 * the exchange rate of the day. Recorded together they make the margin
 * recoverable long after the rate has moved.
 */
final readonly class QuoteData
{
    public function __construct(
        public string $itemsAmount,
        public string $shippingAmount,
        public string $currency,
        public ?string $notes = null,
        public ?string $costAmount = null,
        public ?string $costCurrency = null,
        public ?string $exchangeRate = null,
    ) {}

    /**
     * @param  array{items_amount: mixed, shipping_amount: mixed, currency?: string|null, notes?: string|null, cost_amount?: mixed, cost_currency?: string|null, exchange_rate?: mixed}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            itemsAmount: number_format((float) $attributes['items_amount'], 2, '.', ''),
            shippingAmount: number_format((float) $attributes['shipping_amount'], 2, '.', ''),
            currency: $attributes['currency'] ?? config('shoprelle.quote_currency'),
            notes: isset($attributes['notes']) ? trim((string) $attributes['notes']) ?: null : null,
            costAmount: self::optionalDecimal($attributes['cost_amount'] ?? null, 2),
            costCurrency: $attributes['cost_currency'] ?? null,
            exchangeRate: self::optionalDecimal($attributes['exchange_rate'] ?? null, 6),
        );
    }

    public function totalAmount(): string
    {
        return number_format((float) $this->itemsAmount + (float) $this->shippingAmount, 2, '.', '');
    }

    /**
     * What the request earns once the goods are converted into the currency the
     * customer is billed in, or null when the cost side was left blank.
     */
    public function marginAmount(): ?string
    {
        if ($this->costAmount === null || $this->exchangeRate === null) {
            return null;
        }

        $cost = (float) $this->costAmount * (float) $this->exchangeRate;

        return number_format((float) $this->totalAmount() - $cost, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $hasCost = $this->costAmount !== null;

        return [
            'quote_items_amount' => $this->itemsAmount,
            'quote_shipping_amount' => $this->shippingAmount,
            'quote_total_amount' => $this->totalAmount(),
            'quote_currency' => $this->currency,
            'quote_cost_amount' => $this->costAmount,
            // The currency and rate only mean something next to an amount, so
            // they are cleared together rather than left as orphans.
            'quote_cost_currency' => $hasCost ? $this->costCurrency : null,
            'quote_exchange_rate' => $hasCost ? $this->exchangeRate : null,
            'quote_notes' => $this->notes,
            'quote_sent_at' => now(),
        ];
    }

    /**
     * Normalise an optional amount, treating a blank form field as absent
     * rather than as zero.
     */
    private static function optionalDecimal(mixed $value, int $decimals): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, $decimals, '.', '');
    }
}
