<?php

use App\Enums\PaymentMethod;
use App\Enums\PurchaseRequestStatus;
use App\Models\Payment;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->admin = User::factory()->admin()->create();

    // The `quoted` state bills 60 000 XAF, which every amount below is a
    // fraction of.
    $this->request = PurchaseRequest::factory()->quoted()->create();
});

/**
 * Post a payment with sensible defaults, overridden per test.
 *
 * @param  array<string, mixed>  $attributes
 */
function postPayment(PurchaseRequest $request, array $attributes = []): Illuminate\Testing\TestResponse
{
    return test()->post(route('admin.requests.payments.store', $request), [
        'amount' => '60000',
        'currency' => 'XAF',
        'method' => PaymentMethod::MobileMoney->value,
        'received_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ...$attributes,
    ]);
}

it('records a payment attributed to the administrator who keyed it in', function () {
    $this->actingAs($this->admin);

    postPayment($this->request, [
        'provider' => 'Orange Money',
        'provider_reference' => 'MP240801.1432.A54321',
        'notes' => '  Reçu en agence.  ',
    ])->assertRedirect();

    $payment = Payment::query()->sole();

    expect($payment->amount)->toBe('60000.00')
        ->and($payment->currency)->toBe('XAF')
        ->and($payment->method)->toBe(PaymentMethod::MobileMoney)
        ->and($payment->provider)->toBe('Orange Money')
        ->and($payment->provider_reference)->toBe('MP240801.1432.A54321')
        ->and($payment->notes)->toBe('Reçu en agence.')
        ->and($payment->recorded_by)->toBe($this->admin->id);
});

it('moves the request to payment received once the quote is fully covered', function () {
    $this->actingAs($this->admin);

    postPayment($this->request)->assertRedirect();

    $this->request->refresh()->load('payments');

    expect($this->request->status)->toBe(PurchaseRequestStatus::PaymentReceived)
        ->and($this->request->isSettled())->toBeTrue()
        ->and($this->request->balance())->toBe('0.00');
});

it('leaves a partially paid request waiting on its balance', function () {
    $this->actingAs($this->admin);

    postPayment($this->request, ['amount' => '25000'])->assertRedirect();

    $this->request->refresh()->load('payments');

    expect($this->request->status)->toBe(PurchaseRequestStatus::QuoteSent)
        ->and($this->request->isSettled())->toBeFalse()
        ->and($this->request->totalPaid())->toBe('25000.00')
        ->and($this->request->balance())->toBe('35000.00');
});

it('settles the request once instalments add up to the total', function () {
    $this->actingAs($this->admin);

    postPayment($this->request, ['amount' => '25000'])->assertRedirect();
    postPayment($this->request, ['amount' => '35000'])->assertRedirect();

    $this->request->refresh()->load('payments');

    expect($this->request->payments)->toHaveCount(2)
        ->and($this->request->status)->toBe(PurchaseRequestStatus::PaymentReceived)
        ->and($this->request->totalPaid())->toBe('60000.00');
});

it('settles the request when the customer overpays', function () {
    $this->actingAs($this->admin);

    // Mobile money fees are sometimes added on the sender's side, so more
    // arrives than was quoted. That must not leave the order stuck.
    postPayment($this->request, ['amount' => '60500'])->assertRedirect();

    $this->request->refresh()->load('payments');

    expect($this->request->status)->toBe(PurchaseRequestStatus::PaymentReceived)
        ->and($this->request->balance())->toBe('-500.00');
});

it('accepts a payment after the parcel has shipped', function () {
    // The balance is commonly settled on delivery, long past "quote sent".
    $request = PurchaseRequest::factory()->quoted()->create([
        'status' => PurchaseRequestStatus::Shipped,
    ]);

    $this->actingAs($this->admin);

    postPayment($request)->assertRedirect();

    expect($request->refresh()->status)->toBe(PurchaseRequestStatus::Shipped)
        ->and(Payment::query()->where('purchase_request_id', $request->id)->count())->toBe(1);
});

it('refuses a payment in a currency other than the quote', function () {
    $this->actingAs($this->admin);

    postPayment($this->request, ['currency' => 'EUR'])
        ->assertSessionHasErrors('currency');

    expect(Payment::query()->count())->toBe(0);
});

it('refuses a payment dated in the future', function () {
    $this->actingAs($this->admin);

    postPayment($this->request, ['received_at' => now()->addDay()->format('Y-m-d\TH:i')])
        ->assertSessionHasErrors('received_at');
});

it('refuses a zero or negative payment', function () {
    $this->actingAs($this->admin);

    postPayment($this->request, ['amount' => '0'])->assertSessionHasErrors('amount');
    postPayment($this->request, ['amount' => '-100'])->assertSessionHasErrors('amount');

    expect(Payment::query()->count())->toBe(0);
});

it('refuses a payment on a request that has never been quoted', function () {
    $request = PurchaseRequest::factory()->create();

    $this->actingAs($this->admin);

    postPayment($request)->assertForbidden();

    expect(Payment::query()->count())->toBe(0);
});

it('refuses a payment on a cancelled request', function () {
    $request = PurchaseRequest::factory()->quoted()->create([
        'status' => PurchaseRequestStatus::Cancelled,
    ]);

    $this->actingAs($this->admin);

    postPayment($request)->assertForbidden();
});

it('refuses a payment from a non-administrator', function () {
    $this->actingAs(User::factory()->create());

    postPayment($this->request)->assertForbidden();
});

it('refuses a payment from a guest', function () {
    postPayment($this->request)->assertRedirect(route('login'));
});

it('exposes the running balance on the detail screen', function () {
    Payment::factory()->for($this->request)->amount('20000.00')->create();

    $this->actingAs($this->admin)
        ->get(route('admin.requests.show', $this->request))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('request.payments.total_paid', '20000.00')
            ->where('request.payments.balance', '40000.00')
            ->where('request.payments.is_settled', false)
            ->has('request.payments.entries', 1)
            ->has('paymentMethods')
            ->where('canRecordPayment', true)
        );
});
