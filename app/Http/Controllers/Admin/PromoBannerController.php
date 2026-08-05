<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le bandeau de promotion de la vitrine, contrôlé sans déployer.
 *
 * Un interrupteur et deux messages — le français, et l'anglais depuis que la
 * vitrine parle les deux. Ce qui est enregistré ici prime sur le défaut de
 * config/shoprelle.php.
 */
class PromoBannerController extends Controller
{
    private const SETTING_KEY = 'promo_banner';

    public function edit(): Response
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        return Inertia::render('admin/banner/edit', [
            'banner' => Setting::valueFor(
                self::SETTING_KEY,
                config('shoprelle.promo_banner'),
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            /*
             * Un bandeau est une ligne : au-delà, il devient un paragraphe
             * posé sur le site et pousse le contenu sous la pliure.
             */
            'message' => ['required', 'string', 'max:160'],
            'message_en' => ['nullable', 'string', 'max:160'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => [
                'enabled' => $validated['enabled'],
                'message' => $validated['message'],
                'message_en' => $validated['message_en'] ?? '',
            ]],
        );

        return back()->with('status', 'Bandeau mis à jour.');
    }
}
