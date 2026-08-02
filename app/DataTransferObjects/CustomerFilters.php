<?php

namespace App\DataTransferObjects;

use Illuminate\Http\Request;

/**
 * Filters for the admin customer list, normalised once so the repository never
 * touches the HTTP request.
 */
final readonly class CustomerFilters
{
    /**
     * @param  'created_at'|'last_name'|'purchase_requests_count'  $sort
     * @param  'asc'|'desc'  $direction
     */
    public function __construct(
        public ?string $search = null,
        public ?string $country = null,
        public string $sort = 'created_at',
        public string $direction = 'desc',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: self::nullableString($request->input('search')),
            country: self::country($request->input('country')),
            sort: self::sort((string) $request->string('sort', 'created_at')),
            direction: strtolower((string) $request->string('direction', 'desc')) === 'asc' ? 'asc' : 'desc',
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'country' => $this->country,
            'sort' => $this->sort,
            'direction' => $this->direction,
        ];
    }

    /**
     * Only whitelisted columns reach the query, so no request input can become
     * a raw ORDER BY fragment.
     *
     * @return 'created_at'|'last_name'|'purchase_requests_count'
     */
    private static function sort(string $value): string
    {
        return match ($value) {
            'last_name' => 'last_name',
            'purchase_requests_count' => 'purchase_requests_count',
            default => 'created_at',
        };
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
}
