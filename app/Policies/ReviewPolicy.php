<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

/**
 * Reviews are written by the public but read only by the back office: nothing
 * on the site displays one yet, and the day something does it will read the
 * approved ones rather than all of them.
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
}
