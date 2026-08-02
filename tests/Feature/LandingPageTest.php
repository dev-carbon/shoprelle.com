<?php

it('renders the landing page with its contact details', function () {
    config([
        'shoprelle.contact.email' => 'bonjour@shoprelle.test',
        'shoprelle.contact.response_time' => 'sous 24 h ouvrées',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->where('contact.email', 'bonjour@shoprelle.test')
            ->where('contact.responseTime', 'sous 24 h ouvrées')
        );
});

it('advertises the Telegram bot once it is configured', function () {
    config([
        'services.telegram.token' => '123456:test-token',
        'services.telegram.username' => 'ShoprelleBot',
    ]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('telegramUrl', 'https://t.me/ShoprelleBot')
        );
});

it('accepts a bot username written with its leading at sign', function () {
    config([
        'services.telegram.token' => '123456:test-token',
        'services.telegram.username' => '@ShoprelleBot',
    ]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('telegramUrl', 'https://t.me/ShoprelleBot')
        );
});

it('offers no Telegram link while the bot is incomplete', function (?string $token, ?string $username) {
    config([
        'services.telegram.token' => $token,
        'services.telegram.username' => $username,
    ]);

    // A half-configured bot is worse than none: a link with no token behind it
    // sends a visitor to a conversation nobody is listening to.
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('telegramUrl', null));
})->with([
    'nothing set' => [null, null],
    'token only' => ['123456:test-token', null],
    'username only' => [null, 'ShoprelleBot'],
    'blank username' => ['123456:test-token', ''],
]);

it('lists the destination countries the assistant actually accepts', function () {
    config([
        'shoprelle.countries' => ['CM' => 'Cameroun', 'SN' => 'Sénégal'],
        'shoprelle.delivery_times' => ['CM' => '7 à 14 jours'],
    ]);

    // A list of records rather than a map: the page names the countries and the
    // map draws them, and the drawing joins on the code because the atlas it
    // reads is keyed by ISO numeric id.
    //
    // Senegal carries no estimate because nobody has measured one, and the
    // tooltip drops the line rather than inventing a delay.
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('countries', [
                ['code' => 'CM', 'name' => 'Cameroun', 'deliveryTime' => '7 à 14 jours'],
                ['code' => 'SN', 'name' => 'Sénégal', 'deliveryTime' => null],
            ])
        );
});

it('announces the destinations that are not open yet, separately', function () {
    config([
        'shoprelle.countries' => ['CM' => 'Cameroun'],
        'shoprelle.upcoming_countries' => ['SN' => 'Sénégal'],
        'shoprelle.delivery_times' => [],
    ]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('countries', [['code' => 'CM', 'name' => 'Cameroun', 'deliveryTime' => null]])
            ->where('upcomingCountries', [['code' => 'SN', 'name' => 'Sénégal', 'deliveryTime' => null]])
        );
});

it('quotes a delivery estimate for an announced destination too', function () {
    config([
        'shoprelle.countries' => [],
        'shoprelle.upcoming_countries' => ['SN' => 'Sénégal'],
        'shoprelle.delivery_times' => ['SN' => '10 à 18 jours'],
    ]);

    // The estimate table is keyed independently of which list a country sits
    // in, so a destination can be measured before it opens — and it keeps its
    // figure on the day it moves up rather than losing it in the move.
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('upcomingCountries', [
                ['code' => 'SN', 'name' => 'Sénégal', 'deliveryTime' => '10 à 18 jours'],
            ])
        );
});

it('counts the destinations off the very list the assistant reads', function () {
    config([
        'shoprelle.countries' => ['CM' => 'Cameroun', 'SN' => 'Sénégal'],
        'shoprelle.upcoming_countries' => ['CI' => "Côte d'Ivoire"],
    ]);

    // Derived rather than configured, so the headline figure beside the map and
    // what the conversation actually accepts cannot drift apart.
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.countries', 2)
            ->where('stats.upcoming', 1)
        );
});

it('publishes the network figures once they are set', function () {
    config([
        'shoprelle.stats.parcels_shipped' => '1240',
        'shoprelle.stats.satisfaction_percent' => 98,
    ]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.parcelsShipped', 1240)
            ->where('stats.satisfactionPercent', 98)
        );
});

it('withholds a network figure nobody has earned yet', function (mixed $configured) {
    config([
        'shoprelle.stats.parcels_shipped' => $configured,
        'shoprelle.stats.satisfaction_percent' => $configured,
    ]);

    // These two are claims about the business, not facts about the config: an
    // install that has not been given them leaves the counter out entirely
    // rather than advertising a number nobody earned. "0 colis expédiés" is a
    // worse thing to print than nothing at all.
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.parcelsShipped', null)
            ->where('stats.satisfactionPercent', null)
        );
})->with([
    'unset' => [null],
    'blank' => [''],
    'zero' => [0],
    'negative' => [-5],
    'not a number' => ['bientôt'],
]);

it('links only the social profiles that exist', function () {
    config([
        'shoprelle.social' => [
            'instagram' => 'https://instagram.com/shoprelle',
            'facebook' => '',
            'tiktok' => null,
        ],
    ]);

    // An unset profile is dropped rather than rendered: a footer icon that
    // leads nowhere is worse than one that is not there.
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('social', ['instagram' => 'https://instagram.com/shoprelle'])
        );
});

it('offers a WhatsApp link once a number is configured', function () {
    config(['shoprelle.whatsapp.number' => '+237 6 12 34 56 78']);

    // Le numéro s'écrit comme sur une carte de visite ; `wa.me` n'accepte que
    // les chiffres. La normalisation appartient au contrôleur, pas à la
    // personne qui remplit le .env.
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('whatsappUrl', 'https://wa.me/237612345678?text='.rawurlencode(
                config('shoprelle.whatsapp.greeting')
            ))
        );
});

it('offers no WhatsApp link for a number that cannot be dialled', function (?string $number) {
    config(['shoprelle.whatsapp.number' => $number]);

    // Un numéro national ne peut pas être réparé sans deviner un indicatif :
    // mieux vaut aucun lien qu'un lien qui n'aboutit nulle part.
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('whatsappUrl', null));
})->with([
    'non renseigné' => [null],
    'vide' => [''],
    'sans chiffres' => ['bientôt'],
    'forme nationale' => ['06 12 34 56 78'],
]);
