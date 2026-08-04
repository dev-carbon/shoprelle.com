<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * La sélection de produits est tenue par le back-office et par lui seul.
 *
 * Rien n'y arrive de l'extérieur — contrairement aux avis, qui sont écrits par
 * le public et seulement publiés ici. Les quatre permissions disent donc la
 * même chose, et c'est normal : le partage utile est entre « administrateur »
 * et « pas administrateur », pas entre les gestes.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
