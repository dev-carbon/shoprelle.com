<?php

namespace App\DataTransferObjects;

use App\Enums\Marketplace;

/**
 * One product line as described by the customer.
 */
final readonly class PurchaseItemData
{
    /**
     * @param  list<PendingAttachmentData>  $attachments
     */
    public function __construct(
        public Marketplace $marketplace,
        public string $productUrl,
        public int $quantity,
        public ?string $productName = null,
        public ?string $color = null,
        public ?string $size = null,
        public ?string $variant = null,
        public ?string $declaredPrice = null,
        public ?string $declaredCurrency = null,
        public ?string $comment = null,
        public array $attachments = [],
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        /** @var list<array{disk: string, path: string, original_name: string, mime_type: string, size: int}> $attachments */
        $attachments = $attributes['attachments'] ?? [];

        return new self(
            marketplace: $attributes['marketplace'] instanceof Marketplace
                ? $attributes['marketplace']
                : Marketplace::from((string) $attributes['marketplace']),
            productUrl: (string) $attributes['product_url'],
            quantity: (int) ($attributes['quantity'] ?? 1),
            productName: self::nullableString($attributes['product_name'] ?? null),
            color: self::nullableString($attributes['color'] ?? null),
            size: self::nullableString($attributes['size'] ?? null),
            variant: self::nullableString($attributes['variant'] ?? null),
            declaredPrice: self::nullableString($attributes['declared_price'] ?? null),
            declaredCurrency: self::nullableString($attributes['declared_currency'] ?? null),
            comment: self::nullableString($attributes['comment'] ?? null),
            attachments: array_map(PendingAttachmentData::fromArray(...), $attachments),
        );
    }

    /**
     * Attributes ready to be persisted as a PurchaseItem.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(int $position): array
    {
        return [
            'marketplace' => $this->marketplace,
            'product_url' => $this->productUrl,
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'color' => $this->color,
            'size' => $this->size,
            'variant' => $this->variant,
            'declared_price' => $this->declaredPrice,
            'declared_currency' => $this->declaredPrice !== null ? $this->declaredCurrency : null,
            'comment' => $this->comment,
            'position' => $position,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
