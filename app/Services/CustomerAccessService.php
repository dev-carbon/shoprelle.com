<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepository;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/**
 * The one door to a customer's own history, wherever they knock.
 *
 * The chatbot and the "mes demandes" page both come through here, and share the
 * same attempt budget: two doors onto the same room, each with its own counter,
 * would be a door with twice the tries.
 */
class CustomerAccessService
{
    /**
     * How many access codes may be tried against one phone number, and over
     * what window. Attempts are counted per number rather than per session or
     * per IP: a session is thrown away by clearing a cookie, and the number is
     * the thing actually being attacked. Five tries an hour turns a
     * six-character code into something no amount of patience gets through.
     */
    public const ATTEMPTS = 5;

    public const DECAY_SECONDS = 3600;

    public function __construct(
        private CustomerRepository $customers,
    ) {}

    /**
     * The customer this phone and code open, or null for any other outcome.
     *
     * A wrong code, an unknown number and a number with no code are one and the
     * same answer on purpose: telling them apart would let anyone check which
     * numbers are customers.
     */
    public function attempt(string $phone, string $code): ?Customer
    {
        $customer = $this->customers->findByPhone($phone);

        if ($customer === null || ! $customer->matchesAccessCode($code)) {
            RateLimiter::hit($this->limiter($phone), self::DECAY_SECONDS);

            return null;
        }

        RateLimiter::clear($this->limiter($phone));

        return $customer;
    }

    /**
     * Remember, for this session only, that the customer proved who they are.
     *
     * The session id is regenerated on the way in so a fixed cookie handed to
     * someone cannot be turned into a session that later becomes identified.
     */
    public function remember(Customer $customer): void
    {
        Session::regenerate();
        Session::put($this->sessionKey(), $customer->id);
    }

    /**
     * The customer this session has proved itself to be, if any.
     */
    public function identified(): ?Customer
    {
        $id = Session::get($this->sessionKey());

        if (! is_int($id)) {
            return null;
        }

        $customer = Customer::query()->find($id);

        // A customer deleted, or whose code was reissued by an administrator,
        // must not keep a session open on the strength of the old one.
        if ($customer === null || $customer->access_code_hash === null) {
            $this->forget();

            return null;
        }

        return $customer;
    }

    public function forget(): void
    {
        Session::forget($this->sessionKey());
    }

    public function hasTooManyAttempts(string $phone): bool
    {
        return RateLimiter::tooManyAttempts($this->limiter($phone), self::ATTEMPTS);
    }

    /**
     * Minutes until this number may try again, rounded up so a wait is never
     * announced as being shorter than it is.
     */
    public function minutesUntilRetry(string $phone): int
    {
        return (int) ceil(RateLimiter::availableIn($this->limiter($phone)) / 60);
    }

    /**
     * Keyed by a hash rather than the number itself: the cache is not the place
     * to leave a list of customer phone numbers in clear.
     */
    private function limiter(string $phone): string
    {
        return 'my-orders:'.sha1($phone);
    }

    private function sessionKey(): string
    {
        return (string) config('shoprelle.customer_area.session_key');
    }
}
