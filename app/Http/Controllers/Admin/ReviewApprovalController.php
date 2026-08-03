<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Publie un avis sur la vitrine, ou l'en retire.
 *
 * Une bascule et non deux routes : l'état visé se déduit de l'état courant, et
 * deux administrateurs qui cliquent en même temps ne peuvent pas produire un
 * résultat que ni l'un ni l'autre n'attendait.
 *
 * `approved_at` porte l'horodatage plutôt qu'un booléen — savoir *quand* un
 * avis est sorti vaut mieux que savoir qu'il est sorti.
 */
class ReviewApprovalController extends Controller
{
    public function __invoke(Review $review): RedirectResponse
    {
        $this->authorize('approve', $review);

        $review->approved_at = $review->isApproved() ? null : now();
        $review->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $review->isApproved()
                ? 'Avis publié sur la page d’accueil.'
                : 'Avis retiré de la page d’accueil.',
        ]);

        return back();
    }
}
