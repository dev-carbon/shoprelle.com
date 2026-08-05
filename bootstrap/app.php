<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RecordPageVisit;
use App\Http\Middleware\SetLocale;
use App\Notifications\ServerErrorOccurred;
use App\Services\NotificationService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Telegram cannot carry a CSRF token. The webhook is authenticated by
        // its shared secret instead, in VerifyTelegramWebhook.
        $middleware->validateCsrfTokens(except: ['telegram/webhook']);

        $middleware->web(append: [
            SetLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            RecordPageVisit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * Il n'y a pas de service de suivi d'erreurs externe : les
         * administrateurs sont prévenus par email, tout de suite et sans
         * passer par la file (si l'application casse, la file l'est
         * peut-être aussi). Trois envois par heure et par classe d'exception,
         * pour qu'une boucle de plantage ne devienne pas une boîte pleine ;
         * le journal du back-office garde de toute façon tout.
         *
         * Le `rescue` sans report est ce qui empêche un serveur mail en panne
         * de re-signaler une erreur depuis le signalement d'erreur lui-même.
         */
        $exceptions->report(function (Throwable $exception): void {
            if (! app()->isProduction()) {
                return;
            }

            RateLimiter::attempt(
                'server-error-alert:'.$exception::class,
                maxAttempts: 3,
                callback: fn () => rescue(
                    fn () => app(NotificationService::class)->notifyAdministrators(
                        new ServerErrorOccurred(
                            $exception,
                            app()->runningInConsole() ? null : request()->fullUrl(),
                        ),
                    ),
                    report: false,
                ),
                decaySeconds: 3600,
            );
        });
    })->create();
