<?php

it('serves the storefront in French by default', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('lang="fr"', escape: false)
        ->assertInertia(fn ($page) => $page->where('locale', 'fr'));
});

it('switches the storefront to English for the whole session', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('home'));

    $this->get(route('home'))
        ->assertSee('lang="en"', escape: false)
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            // Une entrée du dictionnaire, pour prouver qu'il est bien servi
            // à la page — le composant t() fait le reste côté client.
            ->where('translations.Commander', 'Order')
        );
});

it('comes back to French on demand', function () {
    $this->post(route('locale.update'), ['locale' => 'en']);
    $this->post(route('locale.update'), ['locale' => 'fr']);

    $this->get(route('home'))
        ->assertSee('lang="fr"', escape: false)
        ->assertInertia(fn ($page) => $page->where('locale', 'fr'));
});

it('refuses a language the site does not speak', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'de'])
        ->assertSessionHasErrors('locale');

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('locale', 'fr'));
});

it('serves every English entry as a French-keyed string', function () {
    $dictionary = json_decode(
        (string) file_get_contents(lang_path('en.json')),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($dictionary)->toBeArray()->not->toBeEmpty();

    foreach ($dictionary as $french => $english) {
        expect($french)->toBeString()->not->toBe('')
            ->and($english)->toBeString()->not->toBe('');
    }
});
