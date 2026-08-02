<?php

namespace App\Enums;

/**
 * The e-commerce platforms Shoprelle currently buys from on behalf of customers.
 *
 * Adding a case here is enough for the marketplace to appear in the chatbot, the
 * admin filters and validation. No other change is required.
 */
enum Marketplace: string
{
    case Shein = 'shein';
    case Temu = 'temu';
    case Amazon = 'amazon';
    case AliExpress = 'aliexpress';
    case Zara = 'zara';
    case Asos = 'asos';
    case Zalando = 'zalando';
    case Bershka = 'bershka';
    case Other = 'other';

    /**
     * The human readable name shown to customers and administrators.
     */
    public function label(): string
    {
        return match ($this) {
            self::Shein => 'Shein',
            self::Temu => 'Temu',
            self::Amazon => 'Amazon',
            self::AliExpress => 'AliExpress',
            self::Zara => 'Zara',
            self::Asos => 'ASOS',
            self::Zalando => 'Zalando',
            self::Bershka => 'Bershka',
            self::Other => 'Autre site',
        };
    }

    /**
     * Hostnames expected for this marketplace, used to sanity check submitted links.
     *
     * An empty list means any host is accepted.
     *
     * @return list<string>
     */
    public function domains(): array
    {
        return match ($this) {
            self::Shein => ['shein.com', 'fr.shein.com', 'us.shein.com', 'm.shein.com'],
            self::Temu => ['temu.com'],
            self::Amazon => ['amazon.fr', 'amazon.com', 'amazon.de', 'amazon.co.uk', 'amzn.eu', 'amzn.to'],
            self::AliExpress => ['aliexpress.com', 'fr.aliexpress.com', 'a.aliexpress.com'],
            self::Zara => ['zara.com'],
            self::Asos => ['asos.com', 'asos.fr'],
            self::Zalando => ['zalando.fr', 'zalando.com', 'zalando.de', 'zalando.be'],
            self::Bershka => ['bershka.com'],
            self::Other => [],
        };
    }

    /**
     * Whether the given URL host plausibly belongs to this marketplace.
     *
     * The check is intentionally forgiving: a mismatch is surfaced as a hint to
     * the customer, never as a hard rejection, because shortened and localised
     * links are common.
     */
    public function matchesUrl(string $url): bool
    {
        $domains = $this->domains();

        if ($domains === []) {
            return true;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        $host = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The marketplace a link belongs to, or null when no case claims the host.
     *
     * `Other` is skipped rather than returned: it accepts every host, so it
     * would swallow the first URL it saw and the caller could no longer tell a
     * recognised platform from an unrecognised one.
     */
    public static function detect(string $url): ?self
    {
        foreach (self::cases() as $case) {
            if ($case !== self::Other && $case->matchesUrl($url)) {
                return $case;
            }
        }

        return null;
    }

    /**
     * All cases as select options for the frontend.
     *
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
