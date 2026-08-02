<?php

namespace App\DataTransferObjects;

use App\Enums\PaymentMethod;
use Illuminate\Support\Carbon;

/**
 * One instalment received against a quote.
 *
 * Kept separate from the HTTP layer so a payment provider's webhook can build
 * the same object from a very different payload and go through exactly the same
 * recording path.
 */
final readonly class PaymentData
{
    public function __construct(
        public string $amount,
        public string $currency,
        public PaymentMethod $method,
        public Carbon $receivedAt,
        public ?string $provider = null,
        public ?string $providerReference = null,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array{amount: mixed, currency: string, method: PaymentMethod|string, received_at?: mixed, provider?: string|null, provider_reference?: string|null, notes?: string|null}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $method = $attributes['method'] instanceof PaymentMethod
            ? $attributes['method']
            : PaymentMethod::from($attributes['method']);

        return new self(
            amount: number_format((float) $attributes['amount'], 2, '.', ''),
            currency: strtoupper($attributes['currency']),
            method: $method,
            receivedAt: isset($attributes['received_at'])
                ? Carbon::parse($attributes['received_at'])
                : Carbon::now(),
            provider: self::cleaned($attributes['provider'] ?? null),
            providerReference: self::cleaned($attributes['provider_reference'] ?? null),
            notes: self::cleaned($attributes['notes'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'method' => $this->method,
            'received_at' => $this->receivedAt,
            'provider' => $this->provider,
            'provider_reference' => $this->providerReference,
            'notes' => $this->notes,
        ];
    }

    private static function cleaned(?string $value): ?string
    {
        return $value === null ? null : (trim($value) ?: null);
    }
}
