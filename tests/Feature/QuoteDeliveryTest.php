<?php

use App\Chatbot\Channel;
use App\DataTransferObjects\QuoteData;
use App\Models\Customer;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\QuoteSent;
use App\Services\PurchaseRequestStatusService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    config()->set('services.telegram.token', 'test-token');

    Http::fake([
        '*/bot*/sendMessage' => Http::response(['ok' => true, 'result' => []]),
    ]);

    $this->admin = User::factory()->admin()->create();
});

/**
 * A request written from the given channel, carrying two products to price.
 *
 * The address is stated rather than left to the factory, which makes it
 * optional: whether a customer left one is exactly what these tests turn on.
 */
function requestFrom(Channel $channel, ?string $identifier = null, ?string $email = null): PurchaseRequest
{
    $customer = Customer::factory()->create(['email' => $email]);

    $request = PurchaseRequest::factory()->for($customer)->create([
        'channel' => $channel->value,
        'channel_identifier' => $identifier,
    ]);

    PurchaseItem::factory()->for($request)->create([
        'product_name' => 'Nike Air Max',
        'quantity' => 2,
    ]);
    PurchaseItem::factory()->for($request)->create([
        'product_name' => 'Sac Zara',
        'quantity' => 1,
    ]);

    return $request->refresh();
}

/**
 * Quote the request, exactly as the back office does.
 */
function quoteRequest(PurchaseRequest $request, ?string $notes = null): void
{
    $index = 0;
    $amounts = $request->items->mapWithKeys(function (PurchaseItem $item) use (&$index): array {
        return [$item->id => $index++ === 0 ? '45000' : '12000'];
    })->all();

    app(PurchaseRequestStatusService::class)->sendQuote(
        $request,
        QuoteData::fromArray([
            'items' => $amounts,
            'shipping_amount' => '8000',
            'currency' => 'XAF',
            'notes' => $notes,
        ]),
        test()->admin,
    );
}

/**
 * Every message the bot posted, oldest first.
 *
 * @return list<string>
 */
function postedMessages(): array
{
    $texts = [];

    foreach (Http::recorded() as [$request]) {
        /** @var Request $request */
        if (str_contains($request->url(), '/sendMessage')) {
            $texts[] = $request->data()['text'];
        }
    }

    return $texts;
}

it('posts the quote back into the Telegram conversation it came from', function () {
    $request = requestFrom(Channel::Telegram, '4242');

    quoteRequest($request);

    Http::assertSent(fn (Request $sent): bool => str_contains($sent->url(), '/sendMessage')
        && $sent->data()['chat_id'] === 4242);

    expect(postedMessages())->toHaveCount(1);

    $message = postedMessages()[0];

    // Every line, then what they add up to, then the shipping and the total.
    expect($message)->toContain($request->reference)
        ->and($message)->toContain('Nike Air Max (×2) : 45 000 XAF')
        ->and($message)->toContain('Sac Zara (×1) : 12 000 XAF')
        ->and($message)->toContain('Produits : 57 000 XAF')
        ->and($message)->toContain('Livraison : 8 000 XAF')
        ->and($message)->toContain('Total : 65 000 XAF');
});

it('passes on the quote note when there is one', function () {
    quoteRequest(requestFrom(Channel::Telegram, '4242'), 'Délai estimé : 3 semaines.');

    expect(postedMessages()[0])->toContain('Délai estimé : 3 semaines.');
});

it('keeps the purchase cost and the margin out of what the customer reads', function () {
    $request = requestFrom(Channel::Telegram, '4242');

    app(PurchaseRequestStatusService::class)->sendQuote(
        $request,
        QuoteData::fromArray([
            'items' => $request->items->mapWithKeys(fn (PurchaseItem $item): array => [$item->id => '28500'])->all(),
            'shipping_amount' => '8000',
            'currency' => 'XAF',
            'cost_amount' => '75',
            'cost_currency' => 'EUR',
            'exchange_rate' => '655.957',
        ]),
        $this->admin,
    );

    expect(postedMessages()[0])->not->toContain('EUR')
        ->and(postedMessages()[0])->not->toContain('655');
});

it('sends nothing for a web request, which has no conversation to answer', function () {
    quoteRequest(requestFrom(Channel::Web));

    Http::assertNothingSent();
});

it('sends nothing when a Telegram request predates the chat being recorded', function () {
    quoteRequest(requestFrom(Channel::Telegram, identifier: null));

    Http::assertNothingSent();
});

it('emails the quote to the address the customer left', function () {
    Notification::fake();

    $request = requestFrom(Channel::Web, email: 'awa@example.com');

    quoteRequest($request);

    Notification::assertSentTo(
        $request,
        QuoteSent::class,
        fn (QuoteSent $notification, array $channels): bool => in_array('mail', $channels, strict: true),
    );
});

it('does not try to email a customer who never gave an address', function () {
    Notification::fake();

    $request = requestFrom(Channel::Web);

    quoteRequest($request);

    // Nothing at all: no thread to answer, and no address to write to. A
    // notification with no channel is not recorded as sent, which is the
    // truthful outcome rather than an empty delivery.
    Notification::assertNothingSentTo($request);
});

it('writes and posts both when the customer has an address and a conversation', function () {
    Notification::fake();

    $request = requestFrom(Channel::Telegram, '4242', 'awa@example.com');

    quoteRequest($request);

    Notification::assertSentTo(
        $request,
        QuoteSent::class,
        fn (QuoteSent $notification, array $channels): bool => in_array('mail', $channels, strict: true)
            && in_array(TelegramChannel::class, $channels, strict: true),
    );
});

it('words the email with every line and what they add up to', function () {
    $request = requestFrom(Channel::Web, email: 'awa@example.com');

    quoteRequest($request, 'Délai estimé : 3 semaines.');

    $mail = (new QuoteSent($request->refresh()))->toMail($request);

    expect($mail->subject)->toContain($request->reference)
        ->and($mail->introLines)->toContain('Nike Air Max (×2) : 45 000 XAF')
        ->and($mail->introLines)->toContain('Sac Zara (×1) : 12 000 XAF')
        ->and($mail->introLines)->toContain('**Produits : 57 000 XAF — Livraison : 8 000 XAF — Total : 65 000 XAF**')
        ->and($mail->introLines)->toContain('Délai estimé : 3 semaines.')
        // Le devis, pas ce qu'il nous coûte.
        ->and($mail->actionUrl)->toBe(route('orders.index'));
});

it('hands the back office the very message the channels send', function () {
    $request = requestFrom(Channel::Web);

    quoteRequest($request, 'Délai estimé : 3 semaines.');

    $response = $this->actingAs($this->admin)
        ->get(route('admin.requests.show', $request->refresh()));

    $handover = $response->viewData('page')['props']['request']['handover'];

    // Mot pour mot ce que Telegram aurait posté : c'est toute la raison d'être
    // du bloc, et une divergence ici serait invisible autrement.
    expect($handover['message'])
        ->toBe((new QuoteSent($request))->asPlainText())
        ->toContain('Nike Air Max (×2) : 45 000 XAF')
        ->toContain('Total : 65 000 XAF')
        ->toContain('Délai estimé : 3 semaines.');

    // wa.me n'accepte le numéro qu'en chiffres, sans le plus ni les espaces.
    expect($handover['whatsapp_url'])
        ->toStartWith('https://wa.me/'.preg_replace('/\D/', '', $request->customer->phone).'?text=')
        ->toContain(rawurlencode('Total : 65 000 XAF'));
});

it('offers nothing to hand over before a quote exists', function () {
    $request = requestFrom(Channel::Web);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.requests.show', $request));

    expect($response->viewData('page')['props']['request']['handover'])->toBeNull();
});

it('records the quote even when Telegram refuses the message', function () {
    Http::fake([
        '*/bot*/sendMessage' => Http::response(['ok' => false, 'description' => 'chat not found'], 400),
    ]);

    $request = requestFrom(Channel::Telegram, '4242');

    quoteRequest($request);

    // The customer is unreachable, but the back office still holds the quote.
    expect($request->refresh()->quote_total_amount)->toBe('65000.00')
        ->and($request->quote_sent_at)->not->toBeNull();
});
