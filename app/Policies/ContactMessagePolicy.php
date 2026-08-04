<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

/**
 * Les messages viennent du public et sont lus par le back-office. Rien n'en
 * sort : on y répond par téléphone ou par email, avec le moyen que la personne
 * a laissé — marquer un message comme traité est donc la seule écriture.
 */
class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function handle(User $user, ContactMessage $message): bool
    {
        return $user->isAdmin();
    }
}
