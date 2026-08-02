<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * Customer records hold personal contact details, so they are strictly
 * back-office data.
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user may replace a customer's access code.
     *
     * The old code stops working immediately, so this locks the customer out
     * until somebody passes them the new one. Administrators only.
     */
    public function reissueAccessCode(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }
}
