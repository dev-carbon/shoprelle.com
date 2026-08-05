<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Le sélecteur de langue du footer : une préférence de session, rien de plus.
 *
 * Pas de compte à mettre à jour ni d'URL préfixée — le visiteur de la vitrine
 * est anonyme, et sa langue le suit le temps de sa session.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('shoprelle.locales')))],
        ]);

        $request->session()->put(SetLocale::SESSION_KEY, $validated['locale']);

        return back();
    }
}
