<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proves a webhook call really came from Telegram.
 *
 * The endpoint is public and unauthenticated, so the shared secret sent back in
 * X-Telegram-Bot-Api-Secret-Token is the only thing standing between the bot
 * and anyone who guesses the URL.
 */
class VerifyTelegramWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.telegram.webhook_secret');

        // Refuse rather than run unauthenticated when the secret is unset:
        // a misconfigured deployment must not silently accept anything.
        abort_if(! is_string($expected) || $expected === '', 403);

        abort_unless(
            hash_equals($expected, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')),
            403,
        );

        return $next($request);
    }
}
