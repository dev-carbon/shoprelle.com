<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compte le trafic des pages publiques : une ligne par jour, deux compteurs.
 *
 * C'est toute la mesure d'audience du site. Pas de service tiers, pas de
 * cookie supplémentaire, aucun parcours individuel : la session déjà présente
 * sert uniquement à ne compter un visiteur qu'une fois par jour, et la page de
 * confidentialité promet exactement cela. Brancher Google Analytics ici
 * obligerait à lui retirer cette promesse — et à afficher une bannière.
 *
 * Le comptage vient après la réponse et n'a jamais le droit de la casser :
 * une page qui s'affiche vaut plus qu'une visite comptée.
 */
class RecordPageVisit
{
    private const SESSION_KEY = 'shoprelle.visited_on';

    /**
     * Ce qui répond en GET à un anonyme sans être une page qu'un humain lit :
     * le sitemap, servi aux robots, et les captures d'écran, chargées par la
     * page qui les montre.
     */
    private const EXCLUDED_ROUTES = ['sitemap', 'orders.attachments.show'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->isCountable($request, $response)) {
            rescue(fn () => $this->count($request), report: false);
        }

        return $response;
    }

    private function isCountable(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            // Un utilisateur connecté est un membre de l'équipe qui vérifie
            // son propre site, pas une visite.
            && $request->user() === null
            // Un rechargement partiel Inertia rafraîchit des props sur une
            // page déjà comptée.
            && ! $request->headers->has('X-Inertia-Partial-Data')
            && ! in_array($request->route()?->getName(), self::EXCLUDED_ROUTES, true);
    }

    private function count(Request $request): void
    {
        /*
         * `today()` et non sa chaîne : le cast date du modèle enregistre la
         * colonne avec une heure à minuit, et une recherche sur la seule date
         * ne la retrouverait pas — chaque visite tenterait alors de recréer
         * la ligne du jour.
         */
        $visit = PageVisit::query()->firstOrCreate(['day' => today()]);
        $visit->increment('views');

        $today = today()->toDateString();

        if ($request->session()->get(self::SESSION_KEY) !== $today) {
            $visit->increment('visitors');
            $request->session()->put(self::SESSION_KEY, $today);
        }
    }
}
