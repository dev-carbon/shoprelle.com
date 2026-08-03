<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

/**
 * Reviews are written by the public and published by the back office. Nothing
 * reaches the landing page on its own: `approved_at` is what lets one out, and
 * setting it is a decision somebody takes.
 */
class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }

    /**
     * Publier un avis, ou le retirer.
     *
     * La même permission dans les deux sens : décider qu'un avis est public et
     * décider qu'il ne l'est plus sont le même geste, et le retirer doit être
     * au moins aussi facile que le publier.
     */
    public function approve(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }
}
