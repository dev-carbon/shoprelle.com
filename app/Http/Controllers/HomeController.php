<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * The public landing page.
     *
     * A controller rather than `Route::inertia()` with props: those are
     * resolved once when the route table is built, so `route:cache` would bake
     * in whatever the contact details happened to be at deploy time.
     */
    public function __invoke(): Response
    {
        return Inertia::render('welcome', [
            'contact' => [
                'email' => config('shoprelle.contact.email'),
                'responseTime' => config('shoprelle.contact.response_time'),
            ],
            'telegramUrl' => $this->telegramUrl(),
            'countries' => $this->asList(config('shoprelle.countries')),
            'upcomingCountries' => $this->asList(config('shoprelle.upcoming_countries')),
            'stats' => $this->stats(),
            'social' => array_filter(config('shoprelle.social')),
        ]);
    }

    /**
     * Turn a code-keyed country map into a list the page can name and draw.
     *
     * The alpha-2 code travels with the name because the map joins on it: its
     * own features are named in English, and ours are not.
     *
     * @param  array<string, string>  $countries
     * @return list<array{code: string, name: string}>
     */
    private function asList(array $countries): array
    {
        $list = [];

        foreach ($countries as $code => $name) {
            $list[] = ['code' => $code, 'name' => $name];
        }

        return $list;
    }

    /**
     * The figures counted beside the map.
     *
     * The destination count is derived from the same list the assistant reads,
     * so the headline number and what the conversation accepts cannot disagree.
     *
     * The other two are claims about the business rather than facts about the
     * configuration, and they stay null until somebody sets them: the page
     * leaves out a counter it has no value for, which is the only way an
     * unconfigured install cannot end up advertising a number nobody earned.
     *
     * @return array{countries: int, upcoming: int, parcelsShipped: ?int, satisfactionPercent: ?int}
     */
    private function stats(): array
    {
        return [
            'countries' => count(config('shoprelle.countries')),
            'upcoming' => count(config('shoprelle.upcoming_countries')),
            'parcelsShipped' => $this->asCount(config('shoprelle.stats.parcels_shipped')),
            'satisfactionPercent' => $this->asCount(config('shoprelle.stats.satisfaction_percent')),
        ];
    }

    /**
     * A configured figure as a positive integer, or null when there is none.
     *
     * Anything that is not a number a counter could climb to — an empty string,
     * a stray word, a zero — is treated as unset rather than shown, since "0
     * colis expédiés" is a worse thing to print than nothing at all.
     */
    private function asCount(mixed $value): ?int
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }

    /**
     * The public link to the Telegram bot, or null when there is none.
     *
     * A bot with a token but no username cannot be linked to, and one with a
     * username but no token would answer nothing. The channel is advertised
     * only when both are set, so a visitor is never sent to a conversation
     * nobody is listening to.
     */
    private function telegramUrl(): ?string
    {
        $username = ltrim((string) config('services.telegram.username'), '@');

        if ($username === '' || blank(config('services.telegram.token'))) {
            return null;
        }

        return 'https://t.me/'.$username;
    }
}
