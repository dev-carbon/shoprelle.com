<?php

use App\Models\Setting;
use App\Models\User;

it('shows the default banner from the configuration', function () {
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('promoBanner', '-50 % sur la livraison de votre première commande')
        );
});

it('serves the English message when the storefront speaks English', function () {
    $this->post(route('locale.update'), ['locale' => 'en']);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('promoBanner', '50% off delivery on your first order')
        );
});

it('lets an administrator change the message without deploying', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.banner.update'), [
            'enabled' => '1',
            'message' => 'Livraison offerte cette semaine',
            'message_en' => 'Free delivery this week',
        ])
        ->assertSessionHasNoErrors();

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('promoBanner', 'Livraison offerte cette semaine')
        );
});

it('removes the banner entirely once disabled', function () {
    Setting::query()->create([
        'key' => 'promo_banner',
        'value' => ['enabled' => false, 'message' => 'Peu importe', 'message_en' => ''],
    ]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('promoBanner', null));
});

it('keeps guests and non-administrators away from the banner screen', function () {
    $this->get(route('admin.banner.edit'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.banner.edit'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.banner.update'), [
            'enabled' => '1',
            'message' => 'Tentative',
        ])
        ->assertForbidden();
});

it('shows the saved values on the banner screen', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.banner.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/banner/edit')
            ->where('banner.enabled', true)
            ->where('banner.message', '-50 % sur la livraison de votre première commande')
        );
});
