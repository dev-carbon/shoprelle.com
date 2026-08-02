<?php

use App\Chatbot\Channel;
use App\Chatbot\Channels\Telegram\TelegramKeyboard;
use App\Chatbot\HelpTopic;
use App\Chatbot\Intent;
use App\Enums\Marketplace;
use App\Enums\PurchaseRequestStatus;
use App\Models\Customer;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

const CHAT_ID = 4242;

beforeEach(function () {
    config()->set('services.telegram.token', 'test-token');
    config()->set('services.telegram.webhook_secret', 'test-secret');

    Http::fake([
        '*/bot*/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        '*/bot*/answerCallbackQuery' => Http::response(['ok' => true, 'result' => []]),
    ]);

    $this->updateId = 0;
});

/**
 * Post a Telegram update carrying a text message.
 */
function sendText(string $text): TestResponse
{
    return postUpdate([
        'message' => [
            'chat' => ['id' => CHAT_ID],
            'text' => $text,
        ],
    ]);
}

/**
 * Post a Telegram update carrying an inline button press.
 */
function tapButton(string $data): TestResponse
{
    return postUpdate([
        'callback_query' => [
            'id' => 'cb-'.uniqid(),
            'data' => $data,
            'message' => ['chat' => ['id' => CHAT_ID]],
        ],
    ]);
}

/**
 * @param  array<string, mixed>  $payload
 */
function postUpdate(array $payload, ?int $updateId = null): TestResponse
{
    test()->updateId = ($updateId ?? ++test()->updateId);

    return test()->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
        ->postJson(route('telegram.webhook'), ['update_id' => test()->updateId] + $payload);
}

/**
 * Every message body the bot sent, oldest first.
 *
 * @return list<string>
 */
function sentMessages(): array
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

/**
 * The keyboard attached to the most recent message, if any.
 *
 * @return list<list<array{text: string, callback_data: string}>>
 */
function lastKeyboard(): array
{
    $markup = [];

    foreach (Http::recorded() as [$request]) {
        /** @var Request $request */
        if (str_contains($request->url(), '/sendMessage')) {
            $markup = $request->data()['reply_markup']['inline_keyboard'] ?? [];
        }
    }

    return $markup;
}

/**
 * @return list<string>
 */
function lastKeyboardValues(): array
{
    $values = [];

    foreach (lastKeyboard() as $row) {
        foreach ($row as $button) {
            $values[] = $button['callback_data'];
        }
    }

    return $values;
}

it('rejects a webhook call without the shared secret', function () {
    $this->postJson(route('telegram.webhook'), ['update_id' => 1])
        ->assertForbidden();

    Http::assertNothingSent();
});

it('rejects a webhook call with the wrong secret', function () {
    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'guessed')
        ->postJson(route('telegram.webhook'), ['update_id' => 1])
        ->assertForbidden();
});

it('refuses every call when no secret is configured', function () {
    config()->set('services.telegram.webhook_secret', null);

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', '')
        ->postJson(route('telegram.webhook'), ['update_id' => 1])
        ->assertForbidden();
});

it('greets a new chat with the welcome menu and its buttons', function () {
    sendText('/start')->assertOk();

    expect(sentMessages())->toHaveCount(2)
        ->and(sentMessages()[0])->toContain('Bienvenue chez Shoprelle')
        ->and(sentMessages()[1])->toBe('Que souhaitez-vous faire ?')
        ->and(lastKeyboardValues())->toBe([
            Intent::NewOrder->value,
            Intent::TrackOrder->value,
            Intent::MyOrders->value,
            Intent::LeaveReview->value,
            Intent::Help->value,
        ]);
});

it('answers a payload it cannot use without crashing', function () {
    postUpdate(['edited_message' => ['chat' => ['id' => CHAT_ID]]])->assertOk();

    Http::assertNothingSent();
});

it('offers the marketplaces as buttons once a new order starts', function () {
    sendText('/start');
    tapButton(Intent::NewOrder->value)->assertOk();

    expect(sentMessages())->toContain('🌐 Sur quelle plateforme souhaitez-vous acheter ?')
        ->and(lastKeyboardValues())->toContain(Marketplace::Shein->value, Marketplace::Other->value);
});

it('offers a skip button on an optional step and no keyboard on a required one', function () {
    sendText('/start');
    tapButton(Intent::NewOrder->value);
    tapButton(Marketplace::Shein->value);

    // Product URL is required: nothing to tap.
    expect(lastKeyboard())->toBe([]);

    sendText('https://fr.shein.com/p-1.html');

    // Colour is optional.
    expect(lastKeyboardValues())->toBe([TelegramKeyboard::SKIP]);
});

it('carries a whole request from the menu to a persisted purchase request', function () {
    Notification::fake();

    sendText('/start');
    tapButton(Intent::NewOrder->value);
    tapButton(Marketplace::Shein->value);
    sendText('https://fr.shein.com/robe-p-123.html');
    sendText('Noir');
    sendText('M');
    sendText('2');
    sendText('29,90');
    tapButton(TelegramKeyboard::SKIP); // screenshot
    sendText('Sans logo');
    tapButton('no'); // no other product
    tapButton('CM');
    sendText('Douala');
    sendText('+237 6 12 34 56 78');
    sendText('Awa Ndiaye');
    sendText('awa@example.com');

    // The recap is spelled out before the confirmation question.
    expect(sentMessages())->toContain('Voici le récapitulatif de votre demande. Tout est correct ?');

    $recap = collect(sentMessages())->first(fn (string $text) => str_contains($text, 'Récapitulatif'));

    expect($recap)->toContain('Shein')
        ->and($recap)->toContain('robe-p-123')
        ->and($recap)->toContain('Quantité : 2')
        ->and($recap)->toContain('Awa Ndiaye')
        ->and($recap)->toContain('Douala, Cameroun');

    tapButton('confirm');

    $request = PurchaseRequest::query()->with(['customer', 'items'])->sole();

    expect($request->channel)->toBe(Channel::Telegram->value)
        ->and($request->status)->toBe(PurchaseRequestStatus::New)
        ->and($request->customer->full_name)->toBe('Awa Ndiaye')
        ->and($request->customer->phone)->toBe('+237612345678')
        ->and($request->items)->toHaveCount(1)
        ->and($request->items->first()->quantity)->toBe(2)
        ->and($request->items->first()->color)->toBe('Noir');

    // The customer is handed their reference, and the only button left offers
    // to start over.
    expect(collect(sentMessages())->filter(
        fn (string $text) => str_contains($text, $request->reference),
    ))->not->toBeEmpty()
        ->and(lastKeyboardValues())->toBe([TelegramKeyboard::MENU]);
});

it('re-asks in place when an answer is invalid', function () {
    sendText('/start');
    tapButton(Intent::NewOrder->value);
    tapButton(Marketplace::Zara->value);

    sendText('https://www.temu.com/p-1.html');

    expect(collect(sentMessages())->last())->toContain('ne semble pas venir de Zara');
});

it('downloads a photo and attaches it to the product', function () {
    Notification::fake();
    Storage::fake('local');

    $jpeg = base64_decode(
        '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0a'
        .'HBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAA'
        .'AAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q=='
    );

    Http::fake([
        '*/bot*/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        '*/bot*/answerCallbackQuery' => Http::response(['ok' => true, 'result' => []]),
        '*/bot*/getFile' => Http::response([
            'ok' => true,
            'result' => ['file_path' => 'photos/file_1.jpg'],
        ]),
        '*/file/bot*' => Http::response($jpeg),
    ]);

    sendText('/start');
    tapButton(Intent::NewOrder->value);
    tapButton(Marketplace::Shein->value);
    sendText('https://fr.shein.com/p-1.html');
    tapButton(TelegramKeyboard::SKIP); // colour
    tapButton(TelegramKeyboard::SKIP); // size
    sendText('1');
    tapButton(TelegramKeyboard::SKIP); // price, lands on the screenshot step

    postUpdate([
        'message' => [
            'chat' => ['id' => CHAT_ID],
            'photo' => [
                ['file_id' => 'small-id'],
                ['file_id' => 'large-id'],
            ],
        ],
    ])->assertOk();

    expect(collect(sentMessages())->last())->toContain('Capture reçue');

    tapButton(TelegramKeyboard::SKIP); // leave the screenshot step
    tapButton(TelegramKeyboard::SKIP); // item comment
    tapButton('no');
    tapButton('CM');
    sendText('Douala');
    sendText('+237612345678');
    sendText('Awa Ndiaye');
    tapButton(TelegramKeyboard::SKIP); // email
    tapButton('confirm');

    $item = PurchaseRequest::query()->sole()->items()->with('attachments')->sole();

    expect($item->attachments)->toHaveCount(1)
        ->and($item->attachments->first()->mime_type)->toBe('image/jpeg');

    Storage::disk('local')->assertExists($item->attachments->first()->path);
});

it('tells the customer when a photo cannot be retrieved', function () {
    Storage::fake('local');

    Http::fake([
        '*/bot*/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        '*/bot*/answerCallbackQuery' => Http::response(['ok' => true, 'result' => []]),
        '*/bot*/getFile' => Http::response(['ok' => false], 400),
    ]);

    sendText('/start');
    tapButton(Intent::NewOrder->value);
    tapButton(Marketplace::Shein->value);
    sendText('https://fr.shein.com/p-1.html');
    tapButton(TelegramKeyboard::SKIP);
    tapButton(TelegramKeyboard::SKIP);
    sendText('1');
    tapButton(TelegramKeyboard::SKIP);

    postUpdate([
        'message' => [
            'chat' => ['id' => CHAT_ID],
            'photo' => [['file_id' => 'broken-id']],
        ],
    ])->assertOk();

    expect(collect(sentMessages())->last())->toContain("n'a pas pu être enregistrée");
});

it('refuses a photo that is not really an image', function () {
    Storage::fake('local');

    Http::fake([
        '*/bot*/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        '*/bot*/answerCallbackQuery' => Http::response(['ok' => true, 'result' => []]),
        '*/bot*/getFile' => Http::response([
            'ok' => true,
            'result' => ['file_path' => 'photos/file_1.jpg'],
        ]),
        '*/file/bot*' => Http::response('<?php echo "owned";'),
    ]);

    sendText('/start');
    tapButton(Intent::NewOrder->value);
    tapButton(Marketplace::Shein->value);
    sendText('https://fr.shein.com/p-1.html');
    tapButton(TelegramKeyboard::SKIP);
    tapButton(TelegramKeyboard::SKIP);
    sendText('1');
    tapButton(TelegramKeyboard::SKIP);

    postUpdate([
        'message' => [
            'chat' => ['id' => CHAT_ID],
            'photo' => [['file_id' => 'evil-id']],
        ],
    ])->assertOk();

    expect(collect(sentMessages())->last())->toContain("n'est pas une image reconnue");
});

it('tracks a request over Telegram', function () {
    $customer = Customer::factory()->create(['phone' => '+237612345678']);
    $request = PurchaseRequest::factory()->for($customer)->quoted()->create();
    PurchaseItem::factory()->count(2)->for($request)->create();

    sendText('/start');
    tapButton(Intent::TrackOrder->value);
    sendText($request->reference);
    sendText('+237612345678');

    $result = collect(sentMessages())->first(fn (string $text) => str_contains($text, '📦 Demande'));

    expect($result)->toContain($request->reference)
        ->and($result)->toContain('Devis envoyé')
        ->and($result)->toContain('Produits : 2');
});

it('offers the help subjects on /aide and answers the one tapped', function () {
    sendText('/start');
    sendText('/aide');

    expect(collect(sentMessages())->last())->toBe('Sur quoi puis-je vous renseigner ?');

    tapButton(HelpTopic::Sites->value);

    expect(collect(sentMessages())->first(fn (string $t) => str_contains($t, 'Shein, Temu, Amazon')))
        ->not->toBeNull()
        // Still in the help menu, so another subject can be tapped straight away.
        ->and(collect(sentMessages())->last())->toBe('Sur quoi puis-je vous renseigner ?');
});

it('lets /menu abandon a request in progress', function () {
    sendText('/start');
    tapButton(Intent::NewOrder->value);
    tapButton(Marketplace::Shein->value);

    sendText('/menu');

    expect(collect(sentMessages())->last())->toBe('Que souhaitez-vous faire ?');
});

it('answers an unknown command with guidance', function () {
    sendText('/start');

    sendText('/bonjour');

    expect(collect(sentMessages())->last())->toContain('Je ne connais pas cette commande');
});

it('ignores a redelivered update instead of applying it twice', function () {
    sendText('/start');
    tapButton(Intent::NewOrder->value);

    $countBefore = count(sentMessages());

    // Telegram retries the same update id after a slow reply.
    postUpdate([
        'callback_query' => [
            'id' => 'cb-repeat',
            'data' => Marketplace::Shein->value,
            'message' => ['chat' => ['id' => CHAT_ID]],
        ],
    ], updateId: 99);

    $countAfterFirst = count(sentMessages());

    postUpdate([
        'callback_query' => [
            'id' => 'cb-repeat',
            'data' => Marketplace::Shein->value,
            'message' => ['chat' => ['id' => CHAT_ID]],
        ],
    ], updateId: 99);

    expect($countAfterFirst)->toBeGreaterThan($countBefore)
        ->and(count(sentMessages()))->toBe($countAfterFirst);
});

it('keeps two chats independent', function () {
    sendText('/start');
    tapButton(Intent::NewOrder->value);
    tapButton(Marketplace::Shein->value);

    // A different customer starts fresh and must land on the menu, not on the
    // product URL question the first one is sitting at.
    postUpdate([
        'message' => [
            'chat' => ['id' => 9999],
            'text' => '/start',
        ],
    ]);

    expect(collect(sentMessages())->last())->toBe('Que souhaitez-vous faire ?');
});
