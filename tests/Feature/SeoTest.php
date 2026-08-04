<?php

it('renders the sharing tags in the HTML the server sends', function () {
    // Le point de ces assertions : Inertia écrit ses balises de tête en
    // JavaScript, et aucun robot d'aperçu n'en exécute — ni WhatsApp, ni
    // Telegram, ni Facebook. Une balise qui n'existe qu'après hydratation
    // n'existe pas pour eux, et c'est en collant un lien dans une conversation
    // que ce service circule. Elles doivent donc être dans la réponse brute.
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('property="og:title"', escape: false)
        ->assertSee('property="og:image"', escape: false)
        ->assertSee('name="twitter:card" content="summary_large_image"', escape: false)
        ->assertSee('name="description"', escape: false)
        ->assertSee('rel="canonical"', escape: false);
});

it('announces the page as French', function () {
    // Le site est intégralement en français. Un `lang` erroné trompe les
    // moteurs autant que les lecteurs d'écran.
    $this->get(route('home'))->assertSee('<html lang="fr"', escape: false);
});

it('publishes a machine-readable identity linking the site to its accounts', function () {
    config([
        'shoprelle.social' => [
            'instagram' => 'https://instagram.com/shoprelle',
            'facebook' => null,
        ],
    ]);

    $response = $this->get(route('home'));

    // `@context` et `@type` ressemblent à des directives Blade : écrites
    // ailleurs que dans un bloc @php, elles cassent la compilation du gabarit
    // et la page entière tombe en erreur 500. Ce test est là pour ça.
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);

    expect($matches)->not->toBeEmpty();

    $data = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

    expect($data['@type'])->toBe('Organization')
        ->and($data['sameAs'])->toBe(['https://instagram.com/shoprelle']);
});

it('lists only the public pages in the sitemap', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('home'), escape: false)
        ->assertSee(route('chat.show'), escape: false)
        // Le formulaire, que n'importe qui peut ouvrir et qu'un client qui
        // revient a le droit de trouver dans un moteur.
        ->assertSee(route('orders.index'), escape: false);

    // Rien derrière une session : un moteur qui suit ces adresses ne récolte
    // que des redirections vers un formulaire de connexion.
    $response->assertDontSee('/admin')->assertDontSee('/dashboard');
});

it('turns crawlers away from the requests themselves, not from the form', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    // La barre finale est ce qui sépare les deux : sans elle, la règle
    // fermerait aussi le formulaire que le sitemap vient d'annoncer.
    expect($robots)->toContain('Disallow: /mes-demandes/')
        ->and($robots)->not->toContain("Disallow: /mes-demandes\n");
});
