<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Issues a customer a fresh access code.
 *
 * Codes are stored hashed, so a customer who loses theirs cannot be sent it
 * again — the only remedy is a new one. This is the support path behind that,
 * and the reason the code is announced so insistently when it is first given.
 */
class CustomerAccessCodeController extends Controller
{
    public function __invoke(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('reissueAccessCode', $customer);

        // Clearing first is what makes this a reissue rather than a no-op:
        // ensureAccessCode() deliberately leaves an existing code alone.
        $customer->access_code_hash = null;
        $customer->ensureAccessCode();
        $customer->save();

        // Flashed rather than rendered into the page: it must reach the screen
        // once, and not survive a refresh of it.
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf(
                'Nouveau code pour %s : %s. Communiquez-le maintenant, il ne sera plus affiché.',
                $customer->full_name,
                Customer::formatAccessCode((string) $customer->plainAccessCode),
            ),
        ]);

        return back();
    }
}
