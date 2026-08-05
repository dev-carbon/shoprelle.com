<?php

use App\Models\User;
use App\Notifications\ServerErrorOccurred;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    // report() écrit aussi dans le canal de log par défaut ; inutile de
    // remplir storage/logs avec des exceptions fabriquées par les tests.
    config()->set('logging.default', 'null');
});

it('emails every administrator when an error is reported in production', function () {
    $administrator = User::factory()->admin()->create();
    $customerFacingUser = User::factory()->create();

    $this->app['env'] = 'production';
    report(new RuntimeException('La base est tombée'));
    $this->app['env'] = 'testing';

    Notification::assertSentTo(
        $administrator,
        ServerErrorOccurred::class,
        fn (ServerErrorOccurred $notification, array $channels): bool => $channels === ['mail']
            && str_contains($notification->message, 'La base est tombée')
            && $notification->exceptionClass === RuntimeException::class,
    );

    Notification::assertNotSentTo($customerFacingUser, ServerErrorOccurred::class);
});

it('stays silent outside production', function () {
    User::factory()->admin()->create();

    report(new RuntimeException('Une erreur de développement'));

    Notification::assertNothingSent();
});

it('stops emailing after three occurrences of the same error within the hour', function () {
    $administrator = User::factory()->admin()->create();

    $this->app['env'] = 'production';

    foreach (range(1, 5) as $ignored) {
        report(new RuntimeException('Toujours la même'));
    }

    $this->app['env'] = 'testing';

    Notification::assertSentToTimes($administrator, ServerErrorOccurred::class, 3);
});
