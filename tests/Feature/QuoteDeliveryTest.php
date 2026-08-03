<?php

use App\Chatbot\Channel;
use App\DataTransferObjects\QuoteData;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PurchaseRequestStatusService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.telegram.token', 'test-token');

    Http::fake([
        '*/bot*/sendMessage' => Http::response(['ok' => true, 'result' => []]),
    ]);

    $this->admin = User::factory()->admin()->create();
});

/**
 * A request written from the given channel, carrying two products to price.
 */
function requestFrom(Channel $channel, ?string $identifier = null): PurchaseRequest
{
    $request = PurchaseRequest::factory()->create([
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
