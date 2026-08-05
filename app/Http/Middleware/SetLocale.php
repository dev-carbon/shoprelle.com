<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique la langue choisie par le visiteur, mémorisée en session.
 *
 * Seule la vitrine est traduite pour l'instant, mais la locale est posée pour
 * toute l'application : c'est elle que lit l'attribut `lang` du document, et
 * les pages qui se traduiront plus tard n'auront rien à rebrancher.
 */
class SetLocale
{
    public const SESSION_KEY = 'shoprelle.locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get(self::SESSION_KEY, config('app.locale'));

        if (array_key_exists($locale, config('shoprelle.locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
