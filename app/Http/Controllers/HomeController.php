<?php

namespace App\Http\Controllers;

use App\Models\Review;
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
            'whatsappUrl' => $this->whatsappUrl(),
            'countries' => $this->asList(config('shoprelle.countries')),
            'upcomingCountries' => $this->asList(config('shoprelle.upcoming_countries')),
            'stats' => $this->stats(),
            'reviews' => $this->reviews(),
            'social' => array_filter(config('shoprelle.social')),
        ]);
    }

    /**
     * Turn a code-keyed country map into a list the page can name and draw.
     *
     * The alpha-2 code travels with the name because the map joins on it: the
     * atlas is keyed by ISO numeric code, and the names in it are English.
     *
     * The estimate is attached here rather than looked up in the page, so the
     * component is handed data and never has to know a country exists. A
     * destination with no measured delay carries none, and the tooltip leaves
     * the line out entirely.
     *
     * @param  array<string, string>  $countries
     * @return list<array{code: string, name: string, deliveryTime: ?string}>
     */
    private function asList(array $countries): array
    {
        $list = [];

        foreach ($countries as $code => $name) {
            $list[] = [
                'code' => $code,
                'name' => $name,
                'deliveryTime' => config('shoprelle.delivery_times.'.$code),
            ];
        }

        return $list;
    }

    /**
     * Les avis publiés, du plus récent au plus ancien.
     *
     * `approved()` et rien d'autre : un avis n'atteint la vitrine que si
     * quelqu'un l'a décidé depuis le back-office. La portée le garantit ici, et
     * la colonne `approved_at` le garantit en base.
     *
     * Le nom n'est jamais donné en entier. La plupart des avis sont anonymes —
     * parler à l'assistant ne demande aucun compte — et ceux qui ne le sont pas
     * n'ont pas pour autant consenti à voir leur nom de famille sur une page
     * publique. Le prénom et la ville suffisent à faire une voix ; le reste
     * n'ajoute que du risque.
     *
     * @return list<array{rating: int, comment: string, author: string, place: ?string}>
     */
    private function reviews(): array
    {
        $published = Review::query()
            ->approved()
            ->whereNotNull('comment')
            ->with('customer')
            ->latest('approved_at')
            ->limit(12)
            ->get();

        $reviews = [];

        // Empilés plutôt que projetés : une collection garde les clés des
        // modèles, et ce que la vue attend est une liste.
        foreach ($published as $review) {
            $reviews[] = [
                'rating' => $review->rating,
                'comment' => (string) $review->comment,
                'author' => $review->customer?->first_name ?: 'Client Shoprelle',
                'place' => $review->customer?->city,
            ];
        }

        return $reviews;
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
     * The link that opens a WhatsApp conversation, or null when there is none.
     *
     * `wa.me` only accepts digits — no `+`, no spaces, no leading zeros — so the
     * number is normalised here rather than in the environment, where whoever
     * fills it in will reasonably write it the way it is printed on a card.
     *
     * A `00` prefix is the same thing as `+` and has to go: `00237…` would be
     * read as a number starting with two zeros and lead nowhere.
     *
     * What is left must not start with a zero either. An international number
     * never does — so a leading zero means somebody wrote the national form,
     * which cannot be repaired here without guessing a country code. No link is
     * offered rather than one that silently dials nowhere.
     */
    private function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) config('shoprelle.whatsapp.number')) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '' || str_starts_with($digits, '0')) {
            return null;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode(
            (string) config('shoprelle.whatsapp.greeting')
        );
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
