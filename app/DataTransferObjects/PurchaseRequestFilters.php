<?php

namespace App\DataTransferObjects;

use App\Enums\Marketplace;
use App\Enums\PurchaseRequestStatus;
use Illuminate\Http\Request;

/**
 * The admin list filters, normalised once so the repository never touches the
 * HTTP request and the frontend always receives the values it sent back.
 */
final readonly class PurchaseRequestFilters
{
    /**
     * @param  'created_at'|'updated_at'|'reference'|'status'  $sort
     * @param  'asc'|'desc'  $direction
     */
    public function __construct(
        public ?string $search = null,
        public ?PurchaseRequestStatus $status = null,
        public ?Marketplace $marketplace = null,
        public ?string $country = null,
        public ?string $from = null,
        public ?string $to = null,
        public string $sort = 'created_at',
        public string $direction = 'desc',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: self::nullableString($request->input('search')),
            status: $request->enum('status', PurchaseRequestStatus::class),
            marketplace: $request->enum('marketplace', Marketplace::class),
            country: self::country($request->input('country')),
            from: self::date($request->input('from')),
            to: self::date($request->input('to')),
            sort: self::sort((string) $request->string('sort', 'created_at')),
            direction: self::direction((string) $request->string('direction', 'desc')),
        );
    }

    /**
     * The shape handed back to the frontend so the controls stay in sync.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status?->value,
            'marketplace' => $this->marketplace?->value,
            'country' => $this->country,
            'from' => $this->from,
            'to' => $this->to,
            'sort' => $this->sort,
            'direction' => $this->direction,
        ];
    }

    /**
     * Query parameters to preserve across pagination links.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter(
            $this->toArray(),
            fn (?string $value): bool => $value !== null && $value !== '',
        );
    }

    /**
     * Only whitelisted columns reach the query, so no request input can ever
     * become a raw ORDER BY fragment.
     *
     * @return 'created_at'|'updated_at'|'reference'|'status'
     */
    private static function sort(string $value): string
    {
        return match ($value) {
            'updated_at' => 'updated_at',
            'reference' => 'reference',
            'status' => 'status',
            default => 'created_at',
        };
    }

    /**
     * @return 'asc'|'desc'
     */
    private static function direction(string $value): string
    {
        return strtolower($value) === 'asc' ? 'asc' : 'desc';
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function country(mixed $value): ?string
    {
        $value = self::nullableString($value);

        if ($value === null) {
            return null;
        }

        $value = strtoupper($value);

        return array_key_exists($value, config('shoprelle.countries')) ? $value : null;
    }

    /**
     * Only accept plain Y-m-d dates; anything else is dropped rather than
     * forwarded to the database.
     */
    private static function date(mixed $value): ?string
    {
        $value = self::nullableString($value);

        if ($value === null) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
}
