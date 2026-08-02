<?php

use App\Enums\Marketplace;
use App\Enums\PurchaseRequestStatus;
use App\Models\Customer;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('keeps guests and non-administrators out of the statistics screen', function () {
    $this->get(route('admin.statistics'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.statistics'))
        ->assertForbidden();
});

it('renders with no data at all', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.statistics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/statistics/index')
            ->where('statistics.headline.requests_in_period', 0)
            ->where('statistics.headline.average_quote', '0.00')
            ->where('statistics.funnel.0.share', 0)
            ->has('statistics.daily', 30)
        );
});

it('reports the headline figures for the period', function () {
    $customer = Customer::factory()->create();

    PurchaseRequest::factory()->count(2)->for($customer)->create();
    PurchaseRequest::factory()->for($customer)->quoted()->create();

    // Outside a 7-day window, inside a 30-day one.
    PurchaseRequest::factory()->create(['created_at' => now()->subDays(20)]);

    $this->actingAs($this->admin)
        ->get(route('admin.statistics', ['period' => 7]))
        ->assertInertia(fn ($page) => $page
            ->where('statistics.period_days', 7)
            ->where('statistics.headline.requests_in_period', 3)
            ->where('statistics.headline.customers_total', 2)
            ->where('statistics.headline.quoted_total', '60000.00')
            ->where('statistics.headline.average_quote', '60000.00')
            ->has('statistics.daily', 7)
        );
});

it('falls back to 30 days when the period is not one it offers', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.statistics', ['period' => 999]))
        ->assertInertia(fn ($page) => $page->where('statistics.period_days', 30));
});

it('fills days with no activity so the series has no gaps', function () {
    PurchaseRequest::factory()->create(['created_at' => now()->subDays(2)]);

    $this->actingAs($this->admin)
        ->get(route('admin.statistics', ['period' => 7]))
        ->assertInertia(fn ($page) => $page
            ->has('statistics.daily', 7)
            ->where('statistics.daily.4.count', 1)
            ->where('statistics.daily.6.count', 0)
        );
});

it('counts a funnel stage from the history, not from the current status', function () {
    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::Cancelled)->create();

    // The request reached "payment received" before being cancelled.
    $request->statusHistories()->create([
        'from_status' => PurchaseRequestStatus::QuoteSent,
        'to_status' => PurchaseRequestStatus::PaymentReceived,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.statistics'))
        ->assertInertia(fn ($page) => $page
            ->where('statistics.funnel.0.label', 'Demandes reçues')
            ->where('statistics.funnel.0.count', 1)
            ->where('statistics.funnel.2.label', 'Paiements reçus')
            ->where('statistics.funnel.2.count', 1)
            ->where('statistics.funnel.5.label', 'Livrées')
            ->where('statistics.funnel.5.count', 0)
        );
});

it('ranks marketplaces and delivery cities by volume', function () {
    $shein = PurchaseRequest::factory()->create(['city' => 'Douala']);
    PurchaseItem::factory()->count(3)->for($shein)->on(Marketplace::Shein)->create();

    $amazon = PurchaseRequest::factory()->create(['city' => 'Douala']);
    PurchaseItem::factory()->for($amazon)->on(Marketplace::Amazon)->create();

    PurchaseRequest::factory()->create(['city' => 'Garoua']);

    $this->actingAs($this->admin)
        ->get(route('admin.statistics'))
        ->assertInertia(fn ($page) => $page
            ->where('statistics.top_marketplaces.0.label', 'Shein')
            ->where('statistics.top_marketplaces.0.count', 3)
            ->where('statistics.top_marketplaces.1.label', 'Amazon')
            ->where('statistics.top_cities.0.label', 'Douala · Cameroun')
            ->where('statistics.top_cities.0.count', 2)
        );
});

it('covers every status in the breakdown, including the empty ones', function () {
    PurchaseRequest::factory()->status(PurchaseRequestStatus::New)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.statistics'))
        ->assertInertia(fn ($page) => $page
            ->has('statistics.by_status', count(PurchaseRequestStatus::cases()))
            ->where('statistics.by_status.0.label', 'Nouveau')
            ->where('statistics.by_status.0.count', 1)
        );
});
