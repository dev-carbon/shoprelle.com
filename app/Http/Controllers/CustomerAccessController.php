<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerAccessRequest;
use App\Services\CustomerAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Opens and closes a customer's own area.
 *
 * There is no account and no password: a phone number and the access code
 * handed out on the first request are the whole credential, and the session
 * remembers the result for as long as it lasts.
 */
class CustomerAccessController extends Controller
{
    /**
     * The one answer given to every failure, so none of them can be told apart.
     * A wrong code, a number that never ordered, and a number whose code was
     * reissued must read identically, or the form becomes a way of finding out
     * which numbers are customers.
     */
    private const REFUSAL = "Numéro ou code d'accès incorrect.";

    public function __construct(
        private CustomerAccessService $access,
    ) {}

    public function store(StoreCustomerAccessRequest $request): RedirectResponse
    {
        $phone = $request->phone();

        if ($this->access->hasTooManyAttempts($phone)) {
            return back()->withErrors(['code' => sprintf(
                'Trop de tentatives. Réessayez dans %d minutes, ou écrivez-nous à %s.',
                $this->access->minutesUntilRetry($phone),
                config('shoprelle.contact.email'),
            )]);
        }

        $customer = $this->access->attempt($phone, $request->code());

        if ($customer === null) {
            return back()->withErrors(['code' => self::REFUSAL]);
        }

        $this->access->remember($customer);

        return to_route('orders.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->access->forget();

        // The whole session goes, not just the key: nothing else on these
        // screens is worth keeping, and a shared computer is the normal case.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('orders.index');
    }
}
