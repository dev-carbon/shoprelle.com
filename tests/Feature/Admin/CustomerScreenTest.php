<?php

use App\Enums\PurchaseRequestStatus;
use App\Models\Customer;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('keeps guests and non-administrators out of the customer list', function () {
    $this->get(route('admin.customers.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.customers.index'))
        ->assertForbidden();
});

it('lists customers with how many requests each has placed', function () {
    $busy = Customer::factory()->create(['first_name' => 'Awa', 'last_name' => 'Ndiaye']);
    PurchaseRequest::factory()->count(3)->for($busy)->create();

    Customer::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index', ['sort' => 'purchase_requests_count']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/index')
            ->has('customers.data', 2)
            ->where('customers.data.0.full_name', 'Awa Ndiaye')
            ->where('customers.data.0.request_count', 3)
            ->where('customers.data.1.request_count', 0)
        );
});

it('paginates with the numbered links the table expects', function () {
    Customer::factory()->count(21)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index'))
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 20)
            ->where('customers.meta.total', 21)
            // Previous, 1, 2, Next — read from `meta`, not the top-level
            // `links`, which only holds first/last/prev/next.
            ->has('customers.meta.links', 4)
            ->where('customers.meta.links.1.active', true)
        );
});

it('searches customers by name, phone, email and city', function () {
    $target = Customer::factory()->create([
        'first_name' => 'Awa',
        'last_name' => 'Ndiaye',
        'phone' => '+237655112233',
        'email' => 'awa@example.com',
        'city' => 'Bafoussam',
    ]);

    Customer::factory()->create(['city' => 'Douala']);

    foreach (['Ndiaye', '655112233', 'awa@example.com', 'Bafoussam'] as $term) {
        $this->actingAs($this->admin)
            ->get(route('admin.customers.index', ['search' => $term]))
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.id', $target->id)
            );
    }
});

it('filters customers by country', function () {
    $cameroon = Customer::factory()->inCountry('CM')->create();
    Customer::factory()->inCountry('SN')->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index', ['country' => 'CM']))
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.id', $cameroon->id)
        );
});

it('ignores an unknown country filter rather than returning nothing', function () {
    Customer::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index', ['country' => 'XX']))
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 2)
            ->where('filters.country', null)
        );
});

it('shows a customer with their request history and totals', function () {
    $customer = Customer::factory()->create();

    $quoted = PurchaseRequest::factory()->for($customer)->quoted()->create();
    PurchaseItem::factory()->count(2)->for($quoted)->create();

    PurchaseRequest::factory()->for($customer)->status(PurchaseRequestStatus::Delivered)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/show')
            ->where('customer.full_name', $customer->full_name)
            ->where('customer.summary.request_count', 2)
            // Delivered is final, so only the quoted request counts as active.
            ->where('customer.summary.active_count', 1)
            ->where('customer.summary.quoted_total', '60000.00')
            ->has('customer.requests', 2)
            ->where('customer.requests.0.item_count', 0)
        );
});

it('does not expose a customer to a non-administrator', function () {
    $customer = Customer::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.customers.show', $customer))
        ->assertForbidden();
});

it('issues a customer a new access code and shows it once', function () {
    $customer = Customer::factory()->withAccessCode()->create();
    $previous = $customer->access_code_hash;

    $this->actingAs($this->admin)
        ->post(route('admin.customers.code.store', $customer))
        ->assertRedirect();

    $customer->refresh();

    expect($customer->access_code_hash)->not->toBe($previous)
        // The old one dies with the reissue, which is why support has to pass
        // the new code on straight away.
        ->and($customer->matchesAccessCode('K4M9PZ'))->toBeFalse();
});

it('never sends the access code itself to the browser', function () {
    $customer = Customer::factory()->withAccessCode()->create();

    $response = $this->actingAs($this->admin)
        ->get(route('admin.customers.show', $customer));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('customer.has_access_code', true)
            ->missing('customer.access_code_hash')
        );

    expect($response->getContent())->not->toContain($customer->access_code_hash);
});

it('gives a customer without a code their first one', function () {
    $customer = Customer::factory()->create();

    expect($customer->access_code_hash)->toBeNull();

    $this->actingAs($this->admin)
        ->post(route('admin.customers.code.store', $customer))
        ->assertRedirect();

    expect($customer->refresh()->access_code_hash)->not->toBeNull();
});

it('refuses to reissue an access code for a non-administrator', function () {
    $customer = Customer::factory()->withAccessCode()->create();

    // Guest first: acting as somebody leaves the session authenticated for the
    // rest of the test, and the redirect would never happen.
    $this->post(route('admin.customers.code.store', $customer))
        ->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->post(route('admin.customers.code.store', $customer))
        ->assertForbidden();

    expect($customer->refresh()->matchesAccessCode('K4M9PZ'))->toBeTrue();
});
