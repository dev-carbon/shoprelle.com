<?php

use App\Enums\PurchaseRequestStatus;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PurchaseRequestStatusChanged;
use Database\Factories\CustomerFactory;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    $this->customer = Customer::factory()->withAccessCode()->create([
        'first_name' => 'Awa',
        'phone' => '+237612345678',
    ]);
});

/**
 * Sign in the way a customer does, through the form.
 */
function identify(?string $phone = null, ?string $code = null): void
{
    test()->post(route('orders.access.store'), [
        'phone' => $phone ?? '+237612345678',
        'code' => $code ?? CustomerFactory::TEST_ACCESS_CODE,
    ]);
}

it('shows the access form to a visitor who has not identified themselves', function () {
    $this->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('orders/access'));
});

it('opens the area with the right number and code', function () {
    $this->post(route('orders.access.store'), [
        'phone' => '+237612345678',
        'code' => CustomerFactory::TEST_ACCESS_CODE,
    ])->assertRedirect(route('orders.index'));

    $this->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('orders/index')
            ->where('customer.first_name', 'Awa')
        );
});

it('accepts the code however the customer spaces and cases it', function () {
    identify(code: 'k4m-9pz');

    $this->get(route('orders.index'))
        ->assertInertia(fn ($page) => $page->component('orders/index'));
});

it('accepts the number however it is punctuated', function () {
    identify(phone: '+237 6 12 34 56 78');

    $this->get(route('orders.index'))
        ->assertInertia(fn ($page) => $page->component('orders/index'));
});

it('answers a wrong code, an unknown number and a codeless account identically', function () {
    // A customer who has never been given a code, and a number nobody owns.
    Customer::factory()->create(['phone' => '+237600000000']);

    $phones = [
        '+237612345678',    // known number, wrong code
        '+237699999999',    // number that never ordered
        '+237600000000',    // known number, no code ever issued
    ];

    foreach ($phones as $phone) {
        $this->post(route('orders.access.store'), ['phone' => $phone, 'code' => 'WRONG1'])
            ->assertRedirect()
            // The very same sentence for all three, down to the word.
            ->assertSessionHasErrors(['code' => "Numéro ou code d'accès incorrect."]);

        // Cleared between attempts so the third answer is a refusal rather
        // than a lockout, which is what is being compared.
        RateLimiter::clear('my-orders:'.sha1($phone));
    }
});

it('refuses even the right code once the number has spent its attempts', function () {
    // Spent at the chatbot's door: the budget is shared, so the page inherits
    // the lockout without a single wrong code being typed here.
    for ($attempt = 0; $attempt < 5; $attempt++) {
        RateLimiter::hit('my-orders:'.sha1('+237612345678'), 3600);
    }

    $response = $this->post(route('orders.access.store'), [
        'phone' => '+237612345678',
        'code' => CustomerFactory::TEST_ACCESS_CODE,
    ])->assertSessionHasErrors('code');

    expect($response->getSession()->get('errors')->first('code'))
        ->toContain('Trop de tentatives');

    $this->get(route('orders.index'))
        ->assertInertia(fn ($page) => $page->component('orders/access'));
});

it('stops one machine from sweeping numbers, whatever the number', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('orders.access.store'), [
            'phone' => '+2376000000'.$attempt.'0',
            'code' => 'WRONG1',
        ])->assertRedirect();
    }

    $this->post(route('orders.access.store'), [
        'phone' => '+237600000060',
        'code' => 'WRONG1',
    ])->assertStatus(429);
});

it('lists only the requests of the customer who identified themselves', function () {
    $mine = PurchaseRequest::factory()->for($this->customer)->quoted()->create();
    PurchaseItem::factory()->count(2)->for($mine)->create();

    $someoneElse = PurchaseRequest::factory()->create();

    identify();

    $this->get(route('orders.index'))
        ->assertInertia(fn ($page) => $page
            ->component('orders/index')
            ->has('requests', 1)
            ->where('requests.0.reference', $mine->reference)
            ->where('requests.0.item_count', 2)
            ->where('requests.0.total_amount', '60000.00')
        )
        ->assertDontSee($someoneElse->reference);
});

it('shows a request with its priced lines, its quote and its balance', function () {
    $request = PurchaseRequest::factory()->for($this->customer)->quoted()->create([
        'quote_notes' => 'Délai estimé : 3 semaines.',
    ]);
    PurchaseItem::factory()->for($request)->create([
        'product_name' => 'Nike Air Max',
        'quoted_amount' => '45000',
    ]);
    Payment::factory()->for($request)->create(['amount' => '20000.00']);

    identify();

    $this->get(route('orders.show', $request->reference))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('orders/show')
            ->where('request.reference', $request->reference)
            ->where('request.items.0.name', 'Nike Air Max')
            ->where('request.items.0.quoted_amount', '45000.00')
            ->where('request.quote.total_amount', '60000.00')
            ->where('request.quote.notes', 'Délai estimé : 3 semaines.')
            ->where('request.payments.total_paid', '20000.00')
            ->where('request.payments.balance', '40000.00')
        );
});

it('never exposes what the back office paid or earns', function () {
    $request = PurchaseRequest::factory()->for($this->customer)->quoted()->create([
        'quote_cost_amount' => '75.00',
        'quote_cost_currency' => 'EUR',
        'quote_exchange_rate' => '655.957000',
    ]);
    PurchaseItem::factory()->for($request)->create();
    $request->adminNotes()->create([
        'user_id' => User::factory()->admin()->create()->id,
        'body' => 'Client à rappeler.',
    ]);

    identify();

    $this->get(route('orders.show', $request->reference))
        ->assertInertia(fn ($page) => $page
            ->missing('request.quote.cost_amount')
            ->missing('request.quote.exchange_rate')
            ->missing('request.margin_amount')
            ->missing('request.notes')
        )
        ->assertDontSee('655.957')
        ->assertDontSee('Client à rappeler');
});

it('does not find a request belonging to someone else', function () {
    $theirs = PurchaseRequest::factory()->create();

    identify();

    $this->get(route('orders.show', $theirs->reference))->assertNotFound();
});

it('sends an unidentified visitor back to the form rather than to a request', function () {
    $request = PurchaseRequest::factory()->for($this->customer)->create();

    $this->get(route('orders.show', $request->reference))
        ->assertRedirect(route('orders.index'));
});

it('closes the area on sign out', function () {
    identify();

    $this->post(route('orders.access.destroy'))->assertRedirect(route('orders.index'));

    $this->get(route('orders.index'))
        ->assertInertia(fn ($page) => $page->component('orders/access'));
});

it('closes a session whose access code has since been reissued', function () {
    identify();

    // The administrator reissues the code, which clears the hash until the
    // customer's next request generates a new one.
    $this->customer->access_code_hash = null;
    $this->customer->save();

    $this->get(route('orders.index'))
        ->assertInertia(fn ($page) => $page->component('orders/access'));
});

it('accepts a quote and then says how to pay it', function () {
    Notification::fake();

    config()->set('shoprelle.payment.methods', [
        ['name' => 'MTN Mobile Money', 'account' => '+237 6 70 00 00 00', 'colour' => '#FFCC00'],
        ['name' => 'Orange Money', 'account' => null, 'colour' => '#FF7900'],
    ]);
    config()->set('shoprelle.payment.account_name', 'Shoprelle SARL');

    $request = PurchaseRequest::factory()->for($this->customer)->quoted()->create();
    PurchaseItem::factory()->for($request)->create();

    identify();

    $this->post(route('orders.quote.accept', $request->reference))
        ->assertRedirect(route('orders.show', $request->reference));

    expect($request->refresh()->status)->toBe(PurchaseRequestStatus::QuoteAccepted)
        ->and($request->statusHistories()->latest()->first()->comment)
        ->toBe('Devis accepté par le client.');

    $this->get(route('orders.show', $request->reference))
        ->assertInertia(fn ($page) => $page
            ->where('request.awaits_decision', false)
            // Seul le moyen dont on connaît le compte est proposé : la
            // vitrine peut nommer les autres, mais un écran de règlement ne
            // sert à rien sans un endroit où envoyer l'argent.
            ->has('request.payment_instructions.methods', 1)
            ->where('request.payment_instructions.methods.0.account', '+237 6 70 00 00 00')
            ->where('request.payment_instructions.account_name', 'Shoprelle SARL')
            ->where('request.payment_instructions.amount', '60000.00')
        );
});

it('keeps the collection numbers to itself until the quote is accepted', function () {
    config()->set('shoprelle.payment.methods', [
        ['name' => 'MTN Mobile Money', 'account' => '+237670000000', 'colour' => '#FFCC00'],
    ]);

    $request = PurchaseRequest::factory()->for($this->customer)->quoted()->create();
    PurchaseItem::factory()->for($request)->create();

    identify();

    $this->get(route('orders.show', $request->reference))
        ->assertInertia(fn ($page) => $page
            ->where('request.awaits_decision', true)
            ->where('request.payment_instructions', null)
        )
        ->assertDontSee('237670000000');
});

it('sends a refused quote back to be redone, with the reason', function () {
    Notification::fake();

    $request = PurchaseRequest::factory()->for($this->customer)->quoted()->create();

    identify();

    $this->post(route('orders.quote.decline', $request->reference), [
        'reason' => 'La livraison est trop chère.',
    ])->assertRedirect(route('orders.show', $request->reference));

    // En attente et non annulée : un devis refusé est presque toujours un
    // devis à refaire, et la raison est ce qui dit quoi refaire.
    expect($request->refresh()->status)->toBe(PurchaseRequestStatus::Pending)
        ->and($request->statusHistories()->latest()->first()->comment)
        ->toBe('Devis refusé par le client : La livraison est trop chère.');
});

it('accepts a refusal without a reason', function () {
    Notification::fake();

    $request = PurchaseRequest::factory()->for($this->customer)->quoted()->create();

    identify();

    $this->post(route('orders.quote.decline', $request->reference))->assertRedirect();

    expect($request->refresh()->statusHistories()->latest()->first()->comment)
        ->toBe('Devis refusé par le client.');
});

it('tells the back office when a customer answers', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $request = PurchaseRequest::factory()->for($this->customer)->quoted()->create();

    identify();
    $this->post(route('orders.quote.accept', $request->reference));

    Notification::assertSentTo($admin, PurchaseRequestStatusChanged::class);
});

it('does not let a customer answer a quote that is no longer awaiting one', function () {
    Notification::fake();

    $request = PurchaseRequest::factory()->for($this->customer)
        ->status(PurchaseRequestStatus::Shipped)
        ->create();

    identify();

    // Deux onglets restés ouverts : la page se recharge sur l'état réel plutôt
    // que de reprocher au client d'avoir cliqué.
    $this->post(route('orders.quote.accept', $request->reference))
        ->assertRedirect(route('orders.show', $request->reference));

    expect($request->refresh()->status)->toBe(PurchaseRequestStatus::Shipped);
});

it('refuses to let anyone answer a quote that is not theirs', function () {
    $theirs = PurchaseRequest::factory()->quoted()->create();

    identify();

    $this->post(route('orders.quote.accept', $theirs->reference))->assertNotFound();

    expect($theirs->refresh()->status)->toBe(PurchaseRequestStatus::QuoteSent);
});

it('sends an unidentified visitor back to the form rather than accepting', function () {
    $request = PurchaseRequest::factory()->for($this->customer)->quoted()->create();

    $this->post(route('orders.quote.accept', $request->reference))
        ->assertRedirect(route('orders.index'));

    expect($request->refresh()->status)->toBe(PurchaseRequestStatus::QuoteSent);
});
