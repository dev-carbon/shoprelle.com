<?php

use App\Models\PageVisit;
use App\Models\User;

it('counts page views, and each session as one visitor per day', function () {
    $this->get(route('home'))->assertOk();
    $this->get(route('legal.privacy'))->assertOk();

    $visit = PageVisit::query()->sole();

    expect($visit->day->toDateString())->toBe(now()->toDateString())
        ->and($visit->views)->toBe(2)
        ->and($visit->visitors)->toBe(1);
});

it('counts a fresh session as a new visitor', function () {
    $this->get(route('home'));

    $this->flushSession();
    $this->get(route('home'));

    $visit = PageVisit::query()->sole();

    expect($visit->views)->toBe(2)
        ->and($visit->visitors)->toBe(2);
});

it('never counts the team browsing its own site', function () {
    $administrator = User::factory()->admin()->create();

    $this->actingAs($administrator)->get(route('dashboard'))->assertOk();
    $this->actingAs($administrator)->get(route('home'))->assertOk();

    expect(PageVisit::query()->count())->toBe(0);
});

it('ignores what a human never reads, like the sitemap', function () {
    $this->get(route('sitemap'))->assertOk();

    expect(PageVisit::query()->count())->toBe(0);
});

it('shows the traffic on the statistics screen', function () {
    PageVisit::factory()->create(['day' => today(), 'views' => 12, 'visitors' => 5]);
    PageVisit::factory()->create(['day' => today()->subDay(), 'views' => 8, 'visitors' => 3]);
    // Hors période de 7 jours : ne doit pas compter dans les totaux.
    PageVisit::factory()->create(['day' => today()->subDays(10), 'views' => 100, 'visitors' => 40]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.statistics', ['period' => 7]))
        ->assertInertia(fn ($page) => $page
            ->where('statistics.traffic.views', 20)
            ->where('statistics.traffic.visitors', 8)
            ->has('statistics.traffic.daily', 7)
        );
});
