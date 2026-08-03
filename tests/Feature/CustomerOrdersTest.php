<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use Database\Factories\CustomerFactory;
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
