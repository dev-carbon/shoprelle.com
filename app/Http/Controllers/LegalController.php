<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Les pages légales : mentions légales et politique de confidentialité.
 *
 * Volontairement minimales — un service de bouche-à-oreille n'a pas besoin de
 * trente articles — mais honnêtes : tout ce qu'elles affirment (durées de
 * conservation, absence de traceurs, code d'accès haché) doit rester vrai
 * dans le code. Celui qui change l'un change l'autre.
 */
class LegalController extends Controller
{
    public function mentions(): Response
    {
        return Inertia::render('legal/mentions', $this->legal());
    }

    public function privacy(): Response
    {
        return Inertia::render('legal/privacy', $this->legal());
    }

    /**
     * @return array<string, mixed>
     */
    private function legal(): array
    {
        return [
            'publisher' => config('shoprelle.legal.publisher'),
            'publisherEmail' => config('shoprelle.legal.publisher_email'),
            'developer' => config('shoprelle.legal.developer'),
            'developerEmail' => config('shoprelle.legal.developer_email'),
            'contactEmail' => config('shoprelle.legal.contact_email'),
            'host' => config('shoprelle.legal.host'),
        ];
    }
}
