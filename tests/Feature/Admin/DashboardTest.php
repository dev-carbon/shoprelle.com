<?php

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;

it('shows the back-office overview to an administrator', function () {
    $admin = User::factory()->admin()->create();

    PurchaseRequest::factory()->count(2)->status(PurchaseRequestStatus::New)->create();
    $delivered = PurchaseRequest::factory()->status(PurchaseRequestStatus::Delivered)->create();
    PurchaseItem::factory()->for($delivered)->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('statistics.total', 3)
            // Delivered is a final status and is excluded from the active count.
            ->where('statistics.active', 2)
            ->where('statistics.by_status.new', 2)
            ->where('statistics.by_status.delivered', 1)
            ->has('latestRequests', 3)
            ->has('statuses')
        );
});
