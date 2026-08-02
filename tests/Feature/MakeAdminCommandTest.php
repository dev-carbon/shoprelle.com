<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a verified administrator from the options alone', function () {
    $this->artisan('shoprelle:make-admin', [
        '--email' => 'awa@shoprelle.com',
        '--name' => 'Awa Ndiaye',
        '--password' => 'Concierge!2026#Shop',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'awa@shoprelle.com')->sole();

    expect($user->isAdmin())->toBeTrue()
        ->and($user->name)->toBe('Awa Ndiaye')
        // The back office sits behind `verified`; a console-created account has
        // no inbox to click a link from.
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Concierge!2026#Shop', $user->password))->toBeTrue();
});

it('lets the freshly created administrator reach the back office', function () {
    $this->artisan('shoprelle:make-admin', [
        '--email' => 'awa@shoprelle.com',
        '--name' => 'Awa Ndiaye',
        '--password' => 'Concierge!2026#Shop',
    ])->assertSuccessful();

    $this->actingAs(User::query()->sole())
        ->get(route('admin.requests.index'))
        ->assertOk();
});

it('promotes an existing user instead of failing on the unique email', function () {
    $user = User::factory()->create(['email' => 'paul@shoprelle.com']);

    expect($user->isAdmin())->toBeFalse();

    $this->artisan('shoprelle:make-admin', ['--email' => 'paul@shoprelle.com'])
        ->assertSuccessful();

    expect($user->refresh()->isAdmin())->toBeTrue()
        ->and(User::query()->count())->toBe(1);
});

it('verifies the email of a promoted user who had never confirmed theirs', function () {
    $user = User::factory()->unverified()->create(['email' => 'paul@shoprelle.com']);

    $this->artisan('shoprelle:make-admin', ['--email' => 'paul@shoprelle.com'])
        ->assertSuccessful();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('says nothing changed when the user is already an administrator', function () {
    $user = User::factory()->admin()->create(['email' => 'awa@shoprelle.com']);

    $this->artisan('shoprelle:make-admin', ['--email' => 'awa@shoprelle.com'])
        ->expectsOutputToContain('est déjà administrateur')
        ->assertSuccessful();

    expect($user->refresh()->isAdmin())->toBeTrue();
});

it('refuses a weak password', function () {
    $this->artisan('shoprelle:make-admin', [
        '--email' => 'awa@shoprelle.com',
        '--name' => 'Awa',
        '--password' => 'abc',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('refuses a malformed email', function () {
    $this->artisan('shoprelle:make-admin', [
        '--email' => 'pas-un-email',
        '--name' => 'Awa',
        '--password' => 'Concierge!2026#Shop',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('no longer exposes public registration', function () {
    expect(app('router')->getRoutes()->getByName('register'))->toBeNull();
});
