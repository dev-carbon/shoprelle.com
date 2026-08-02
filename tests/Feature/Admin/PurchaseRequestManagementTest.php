<?php

use App\Enums\Marketplace;
use App\Enums\PurchaseRequestStatus;
use App\Models\Customer;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PurchaseRequestStatusChanged;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('keeps guests out of the request list', function () {
    $this->get(route('admin.requests.index'))->assertRedirect(route('login'));
});

it('keeps non-administrators out of the back office', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.requests.index'))
        ->assertForbidden();
});

it('lists requests for an administrator', function () {
    $request = PurchaseRequest::factory()->create();
    PurchaseItem::factory()->for($request)->on(Marketplace::Shein)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/requests/index')
            ->has('requests.data', 1)
            ->where('requests.data.0.reference', $request->reference)
            ->where('requests.data.0.marketplaces.0', 'Shein')
        );
});

it('paginates with the numbered links the table expects', function () {
    PurchaseRequest::factory()->count(16)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.requests.index'))
        ->assertInertia(fn ($page) => $page
            ->has('requests.data', 15)
            ->where('requests.meta.total', 16)
            // Previous, 1, 2, Next. The frontend reads these from `meta`, not
            // from the top-level `links`, which only holds first/last/prev/next.
            ->has('requests.meta.links', 4)
            ->where('requests.meta.links.1.label', '1')
            ->where('requests.meta.links.1.active', true)
            ->has('requests.links.next')
        );
});

it('filters requests by status', function () {
    PurchaseRequest::factory()->status(PurchaseRequestStatus::New)->create();
    $shipped = PurchaseRequest::factory()->status(PurchaseRequestStatus::Shipped)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.requests.index', ['status' => PurchaseRequestStatus::Shipped->value]))
        ->assertInertia(fn ($page) => $page
            ->has('requests.data', 1)
            ->where('requests.data.0.reference', $shipped->reference)
        );
});

it('filters requests by marketplace', function () {
    $shein = PurchaseRequest::factory()->create();
    PurchaseItem::factory()->for($shein)->on(Marketplace::Shein)->create();

    $amazon = PurchaseRequest::factory()->create();
    PurchaseItem::factory()->for($amazon)->on(Marketplace::Amazon)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.requests.index', ['marketplace' => Marketplace::Amazon->value]))
        ->assertInertia(fn ($page) => $page
            ->has('requests.data', 1)
            ->where('requests.data.0.reference', $amazon->reference)
        );
});

it('filters requests by country and by date range', function () {
    $cameroon = PurchaseRequest::factory()->create([
        'country' => 'CM',
        'created_at' => now()->subDays(2),
    ]);
    PurchaseRequest::factory()->create([
        'country' => 'SN',
        'created_at' => now()->subDays(2),
    ]);
    PurchaseRequest::factory()->create([
        'country' => 'CM',
        'created_at' => now()->subDays(30),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.requests.index', [
            'country' => 'CM',
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('requests.data', 1)
            ->where('requests.data.0.reference', $cameroon->reference)
        );
});

it('searches by reference, by customer and by product link', function () {
    $customer = Customer::factory()->create(['first_name' => 'Awa', 'last_name' => 'Ndiaye']);
    $request = PurchaseRequest::factory()->for($customer)->create();
    PurchaseItem::factory()->for($request)->create([
        'product_url' => 'https://fr.shein.com/robe-longue-p-999.html',
    ]);

    PurchaseRequest::factory()->create();

    $admin = $this->admin;

    foreach ([$request->reference, 'Ndiaye', $customer->phone, 'robe-longue'] as $term) {
        $this->actingAs($admin)
            ->get(route('admin.requests.index', ['search' => $term]))
            ->assertInertia(fn ($page) => $page
                ->has('requests.data', 1)
                ->where('requests.data.0.reference', $request->reference)
            );
    }
});

it('ignores a malformed date filter instead of failing', function () {
    PurchaseRequest::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.requests.index', ['from' => 'not-a-date']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('requests.data', 1)
            ->where('filters.from', null)
        );
});

it('shows a request with its items, history and notes', function () {
    $request = PurchaseRequest::factory()->create();
    PurchaseItem::factory()->count(2)->for($request)->create();
    $request->statusHistories()->create([
        'to_status' => PurchaseRequestStatus::New,
        'comment' => 'Demande créée par le client.',
    ]);
    $request->adminNotes()->create([
        'user_id' => $this->admin->id,
        'body' => 'Vérifier la disponibilité.',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.requests.show', $request))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/requests/show')
            ->where('request.reference', $request->reference)
            ->has('request.items', 2)
            ->has('request.status_history', 1)
            ->where('request.notes.0.body', 'Vérifier la disponibilité.')
            ->has('availableTransitions')
        );
});

it('moves a request to an allowed status and records who did it', function () {
    Notification::fake();

    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::New)->create();

    $this->actingAs($this->admin)
        ->put(route('admin.requests.status.update', $request), [
            'status' => PurchaseRequestStatus::Pending->value,
            'comment' => 'En attente du devis fournisseur.',
        ])
        ->assertRedirect();

    $request->refresh();
    $history = $request->statusHistories()->latest()->first();

    expect($request->status)->toBe(PurchaseRequestStatus::Pending)
        ->and($history->from_status)->toBe(PurchaseRequestStatus::New)
        ->and($history->to_status)->toBe(PurchaseRequestStatus::Pending)
        ->and($history->user_id)->toBe($this->admin->id)
        ->and($history->comment)->toBe('En attente du devis fournisseur.');
});

it('refuses a status change the lifecycle forbids', function () {
    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::New)->create();

    $this->actingAs($this->admin)
        ->put(route('admin.requests.status.update', $request), [
            'status' => PurchaseRequestStatus::Delivered->value,
        ])
        ->assertSessionHasErrors('status');

    expect($request->refresh()->status)->toBe(PurchaseRequestStatus::New)
        ->and($request->statusHistories)->toHaveCount(0);
});

it('refuses to change the status of a closed request', function () {
    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::Delivered)->create();

    $this->actingAs($this->admin)
        ->put(route('admin.requests.status.update', $request), [
            'status' => PurchaseRequestStatus::Cancelled->value,
        ])
        ->assertForbidden();
});

it('notifies the other administrators of a status change but not its author', function () {
    Notification::fake();

    $other = User::factory()->admin()->create();
    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::New)->create();

    $this->actingAs($this->admin)
        ->put(route('admin.requests.status.update', $request), [
            'status' => PurchaseRequestStatus::Pending->value,
        ]);

    Notification::assertSentTo($other, PurchaseRequestStatusChanged::class);
    Notification::assertNotSentTo($this->admin, PurchaseRequestStatusChanged::class);
});

it('records a quote and marks the request as quoted in one step', function () {
    Notification::fake();

    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::Pending)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.requests.quote.store', $request), [
            'items_amount' => '45000',
            'shipping_amount' => '15000.50',
            'currency' => 'xaf',
            'notes' => 'Délai estimé : 3 semaines.',
        ])
        ->assertRedirect();

    $request->refresh();

    expect($request->status)->toBe(PurchaseRequestStatus::QuoteSent)
        ->and($request->quote_items_amount)->toBe('45000.00')
        ->and($request->quote_shipping_amount)->toBe('15000.50')
        ->and($request->quote_total_amount)->toBe('60000.50')
        ->and($request->quote_currency)->toBe('XAF')
        ->and($request->quote_notes)->toBe('Délai estimé : 3 semaines.')
        ->and($request->quote_sent_at)->not->toBeNull();
});

it('refuses a quote on a request that cannot be quoted', function () {
    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::Delivered)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.requests.quote.store', $request), [
            'items_amount' => '100',
            'shipping_amount' => '10',
            'currency' => 'XAF',
        ])
        ->assertForbidden();

    expect($request->refresh()->quote_sent_at)->toBeNull();
});

it('records the purchase cost and rate so the margin survives the exchange rate moving', function () {
    Notification::fake();

    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::Pending)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.requests.quote.store', $request), [
            'items_amount' => '45000',
            'shipping_amount' => '15000',
            'currency' => 'XAF',
            'cost_amount' => '75',
            'cost_currency' => 'eur',
            'exchange_rate' => '655.957',
        ])
        ->assertRedirect();

    $request->refresh();

    // 75 EUR bought at 655.957 costs 49 196.78 XAF against 60 000 billed.
    expect($request->quote_cost_amount)->toBe('75.00')
        ->and($request->quote_cost_currency)->toBe('EUR')
        ->and($request->quote_exchange_rate)->toBe('655.957000')
        ->and($request->marginAmount())->toBe('10803.22');
});

it('leaves the margin unknown rather than guessing when the cost is omitted', function () {
    Notification::fake();

    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::Pending)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.requests.quote.store', $request), [
            'items_amount' => '45000',
            'shipping_amount' => '15000',
            'currency' => 'XAF',
        ])
        ->assertRedirect();

    $request->refresh();

    expect($request->quote_total_amount)->toBe('60000.00')
        ->and($request->quote_cost_amount)->toBeNull()
        ->and($request->marginAmount())->toBeNull();
});

it('requires an exchange rate alongside a purchase cost', function () {
    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::Pending)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.requests.quote.store', $request), [
            'items_amount' => '45000',
            'shipping_amount' => '15000',
            'currency' => 'XAF',
            'cost_amount' => '75',
        ])
        ->assertSessionHasErrors(['cost_currency', 'exchange_rate']);

    expect($request->refresh()->quote_sent_at)->toBeNull();
});

it('validates the quote amounts', function () {
    $request = PurchaseRequest::factory()->status(PurchaseRequestStatus::Pending)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.requests.quote.store', $request), [
            'items_amount' => '-5',
            'shipping_amount' => 'gratuit',
            'currency' => 'EUROS',
        ])
        ->assertSessionHasErrors(['items_amount', 'shipping_amount', 'currency']);
});

it('adds an internal note attributed to its author', function () {
    $request = PurchaseRequest::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.notes.store', $request), ['body' => '  Client rappelé.  '])
        ->assertRedirect();

    $note = $request->adminNotes()->sole();

    expect($note->body)->toBe('Client rappelé.')
        ->and($note->user_id)->toBe($this->admin->id);
});

it('rejects an empty internal note', function () {
    $request = PurchaseRequest::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.notes.store', $request), ['body' => ''])
        ->assertSessionHasErrors('body');
});

it('does not let a non-administrator act on a request', function () {
    $request = PurchaseRequest::factory()->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('admin.requests.show', $request))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->post(route('admin.notes.store', $request), ['body' => 'test'])
        ->assertForbidden();

    expect($request->adminNotes()->count())->toBe(0);
});
