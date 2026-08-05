<?php

it('shows the legal notice with the publisher, the contact and the host', function () {
    config()->set('shoprelle.legal.publisher', 'Awa Ndiaye');

    $this->get(route('legal.mentions'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('legal/mentions')
            ->where('publisher', 'Awa Ndiaye')
            ->where('publisherEmail', 'farelle.kemene@shoprelle.com')
            ->where('developer', 'Hugues TCHOUALA')
            ->where('developerEmail', 'hugues.tchouala@shoprelle.com')
            ->where('contactEmail', 'contact@shoprelle.com')
            ->where('host.name', 'IONOS SARL')
        );
});

it('shows the privacy policy', function () {
    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('legal/privacy')
            ->where('contactEmail', 'contact@shoprelle.com')
            ->where('host.name', 'IONOS SARL')
        );
});
