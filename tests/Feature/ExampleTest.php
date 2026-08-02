<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

it('passes the contact details the landing page displays', function () {
    config()->set('shoprelle.contact.email', 'bonjour@example.test');
    config()->set('shoprelle.contact.response_time', 'sous 2 h');

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->where('contact.email', 'bonjour@example.test')
            ->where('contact.responseTime', 'sous 2 h')
        );
});
