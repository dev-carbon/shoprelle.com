<?php

use App\Models\Customer;
use App\Models\Review;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('keeps guests and non-administrators out of the review list', function () {
    $this->get(route('admin.reviews.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.reviews.index'))
        ->assertForbidden();
});

it('lists reviews newest first with their totals', function () {
    Review::factory()->rated(2)->create(['created_at' => now()->subDay()]);
    Review::factory()->rated(4)->create(['created_at' => now()]);

    $this->actingAs($this->admin)
        ->get(route('admin.reviews.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/reviews/index')
            ->has('reviews.data', 2)
            ->where('reviews.data.0.rating', 4)
            ->where('summary.total', 2)
            // JSON has no way to say "3.0", so a whole average arrives as an
            // int. The browser formats it; the server only rounds it.
            ->where('summary.average', 3)
        );
});

it('shows an empty average rather than a division by zero', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reviews.index'))
        ->assertInertia(fn ($page) => $page
            ->where('summary.total', 0)
            ->where('summary.average', 0)
        );
});

it('names the customer behind an attributed review and nobody behind the rest', function () {
    $customer = Customer::factory()->create(['first_name' => 'Awa', 'last_name' => 'Ndiaye']);

    Review::factory()->for($customer)->create(['created_at' => now()]);
    Review::factory()->create(['created_at' => now()->subDay()]);

    $this->actingAs($this->admin)
        ->get(route('admin.reviews.index'))
        ->assertInertia(fn ($page) => $page
            ->where('reviews.data.0.customer.full_name', 'Awa Ndiaye')
            ->where('reviews.data.1.customer', null)
        );
});
