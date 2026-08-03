<?php

use App\Models\Review;
use App\Models\User;

it('publishes a review, then takes it back off', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $review = Review::factory()->create(['approved_at' => null]);

    // Une bascule et non deux routes : l'état visé se déduit de l'état courant.
    $this->actingAs($admin)
        ->patch(route('admin.reviews.approval', $review))
        ->assertRedirect();

    expect($review->refresh()->isApproved())->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.reviews.approval', $review))
        ->assertRedirect();

    expect($review->refresh()->isApproved())->toBeFalse();
});

it('refuses a signed-in user who is not an administrator', function () {
    $review = Review::factory()->create(['approved_at' => null]);

    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->patch(route('admin.reviews.approval', $review))
        ->assertForbidden();

    expect($review->refresh()->isApproved())->toBeFalse();
});

// Séparé du cas précédent, et non enchaîné après lui : `actingAs` vaut pour
// tout le reste du test, si bien qu'un second appel n'est plus anonyme et
// mesurerait la mauvaise chose.
it('sends a visitor who is not signed in to the login page', function () {
    $review = Review::factory()->create(['approved_at' => null]);

    $this->patch(route('admin.reviews.approval', $review))
        ->assertRedirect(route('login'));

    expect($review->refresh()->isApproved())->toBeFalse();
});
