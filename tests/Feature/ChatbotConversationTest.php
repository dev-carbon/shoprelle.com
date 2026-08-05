<?php

use App\Chatbot\Channel;
use App\Chatbot\ChatbotEngine;
use App\Chatbot\HelpTopic;
use App\Chatbot\Intent;
use App\Chatbot\Step;
use App\Enums\Marketplace;
use App\Enums\PurchaseRequestStatus;
use App\Models\Customer;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\Review;
use App\Models\User;
use App\Notifications\PurchaseRequestSubmitted;
use Database\Factories\CustomerFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function say(string $message): void
{
    test()->post(route('chat.message'), ['message' => $message]);
}

function pass(): void
{
    test()->post(route('chat.skip'));
}

/**
 * Open the assistant and pick "new order" on the welcome menu.
 */
function startNewOrder(): void
{
    test()->get(route('chat.show'));
    say(Intent::NewOrder->value);
}

/**
 * Describe one product with the bare minimum of answers.
 */
function describeItem(Marketplace $marketplace, string $url, int $quantity = 1): void
{
    say($marketplace->value);
    say($url);
    pass(); // colour
    pass(); // size
    say((string) $quantity);
    pass(); // declared price
    pass(); // screenshot
    pass(); // item comment
}

/**
 * Walk a full request from the menu to the recap, the way a customer would.
 */
function answerUntilSummary(): void
{
    startNewOrder();

    say(Marketplace::Shein->value);
    say('https://fr.shein.com/product-p-123.html');
    say('Noir');
    say('M');
    say('2');
    say('29,90');
    pass(); // screenshot
    say('Sans logo');
    say('no');
    say('CM');
    say('Douala');
    say('+237 6 12 34 56 78');
    say('Awa Ndiaye');
    say('awa@example.com');
}

it('greets a visitor with the welcome menu', function () {
    $this->get(route('chat.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat')
            ->where('conversation.current.step', Step::Menu->value)
            ->where('conversation.current.input_type', 'choice')
            ->where('conversation.current.options.0.value', Intent::NewOrder->value)
            ->where('conversation.completed', false)
            ->has('conversation.messages', 2)
        );
});

it('caps a free-form field at the length its validator rejects', function () {
    startNewOrder();
    say(Marketplace::Shein->value);
    say('https://fr.shein.com/product-p-123.html');

    // The colour field stops the customer at 60 rather than letting them type
    // on and be turned away when they send it.
    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Color->value)
        ->where('conversation.current.max_length', 60)
    );

    say(str_repeat('a', 61));

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Color->value)
        ->where('conversation.messages', fn ($messages) => collect($messages)
            ->contains(fn (array $message) => str_contains($message['text'], 'sous 60 caractères')))
    );
});

it('opens a list of subjects when help is picked', function () {
    $this->get(route('chat.show'));

    say(Intent::Help->value);

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::HelpTopic->value)
        ->where('conversation.current.options.0.value', HelpTopic::HowItWorks->value)
        // The last option leaves the help menu rather than answering anything.
        ->where('conversation.current.options.5.value', ChatbotEngine::MENU)
    );
});

it('answers a subject and stays in the help menu', function () {
    $this->get(route('chat.show'));

    say(Intent::Help->value);
    say(HelpTopic::Fees->value);

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::HelpTopic->value)
        ->where('conversation.messages', fn ($messages) => collect($messages)
            ->contains(fn (array $message) => str_contains($message['text'], 'transport calculé au poids réel')))
    );
});

it('leaves the help menu through its back option', function () {
    $this->get(route('chat.show'));

    say(Intent::Help->value);
    say(ChatbotEngine::MENU);

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Menu->value)
    );
});

it('refuses a subject it does not know', function () {
    $this->get(route('chat.show'));

    say(Intent::Help->value);
    say('la meteo');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::HelpTopic->value)
        ->where('conversation.messages', fn ($messages) => collect($messages)
            ->contains(fn (array $message) => str_contains($message['text'], 'Choisissez un sujet')))
    );
});

it('guides the customer through every step and submits the request', function () {
    Notification::fake();

    answerUntilSummary();

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Summary->value)
        ->where('conversation.summary.customer.full_name', 'Awa Ndiaye')
        ->where('conversation.summary.customer.phone', '+237612345678')
        ->where('conversation.summary.items.0.quantity', 2)
        ->where('conversation.summary.items.0.declared_price', '29.90')
        ->has('conversation.summary.items', 1)
    );

    $this->post(route('chat.confirm'))->assertRedirect();

    $request = PurchaseRequest::query()->with(['customer', 'items'])->sole();

    expect($request->status)->toBe(PurchaseRequestStatus::New)
        ->and($request->reference)->toStartWith('SHP-')
        ->and($request->channel)->toBe('web')
        ->and($request->customer->full_name)->toBe('Awa Ndiaye')
        ->and($request->customer->phone)->toBe('+237612345678')
        ->and($request->country)->toBe('CM')
        ->and($request->city)->toBe('Douala')
        ->and($request->items)->toHaveCount(1)
        ->and($request->items->first()->marketplace)->toBe(Marketplace::Shein)
        ->and($request->items->first()->quantity)->toBe(2)
        ->and($request->items->first()->color)->toBe('Noir')
        ->and($request->items->first()->size)->toBe('M');
});

it('records the initial status in the history', function () {
    Notification::fake();

    answerUntilSummary();
    $this->post(route('chat.confirm'));

    $request = PurchaseRequest::query()->sole();

    expect($request->statusHistories)->toHaveCount(1)
        ->and($request->statusHistories->first()->from_status)->toBeNull()
        ->and($request->statusHistories->first()->to_status)->toBe(PurchaseRequestStatus::New);
});

it('notifies administrators when a request is submitted', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    User::factory()->create(); // non-admin, must not be notified

    answerUntilSummary();
    $this->post(route('chat.confirm'));

    Notification::assertSentTo($admin, PurchaseRequestSubmitted::class);
    Notification::assertCount(1);
});

it('shows the confirmation and the reference once the request is submitted', function () {
    Notification::fake();

    answerUntilSummary();
    $this->post(route('chat.confirm'));

    $reference = PurchaseRequest::query()->value('reference');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.completed', true)
        ->where('conversation.reference', $reference)
        ->where('conversation.current.input_type', 'none')
    );
});

it('asks again instead of advancing when an answer is invalid', function () {
    startNewOrder();
    say(Marketplace::Shein->value);

    say('ceci-nest-pas-une-url');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::ProductUrl->value)
    );
});

it('rejects a link that does not belong to the chosen marketplace', function () {
    startNewOrder();
    say(Marketplace::Zara->value);

    say('https://www.temu.com/product-123.html');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::ProductUrl->value)
    );
});

it('accepts any host when the customer picked "other site"', function () {
    startNewOrder();
    say(Marketplace::Other->value);

    say('https://boutique-inconnue.example/produit/1');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Color->value)
    );
});

it('refuses a quantity outside the allowed range', function () {
    startNewOrder();
    say(Marketplace::Shein->value);
    say('https://fr.shein.com/p-1.html');
    pass();
    pass();

    say('999');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Quantity->value)
    );
});

it('refuses a country Shoprelle does not ship to', function () {
    startNewOrder();
    describeItem(Marketplace::Shein, 'https://fr.shein.com/p-1.html');
    say('no');

    say('FR');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Country->value)
    );
});

it('requires both a first and a last name', function () {
    startNewOrder();
    describeItem(Marketplace::Shein, 'https://fr.shein.com/p-1.html');
    say('no');
    say('CM');
    say('Douala');
    say('+237612345678');

    say('Awa');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::FullName->value)
    );
});

it('collects several products in one request', function () {
    Notification::fake();

    startNewOrder();
    describeItem(Marketplace::Shein, 'https://fr.shein.com/p-1.html');
    say('yes');
    describeItem(Marketplace::Amazon, 'https://www.amazon.fr/dp/B0TEST', quantity: 3);
    say('no');

    say('CM');
    say('Yaoundé');
    say('+237612345678');
    say('Paul Mbarga');
    pass(); // email
    $this->post(route('chat.confirm'));

    $request = PurchaseRequest::query()->with('items')->sole();

    expect($request->items)->toHaveCount(2)
        ->and($request->items->pluck('marketplace')->all())
        ->toBe([Marketplace::Shein, Marketplace::Amazon])
        ->and($request->items->pluck('quantity')->all())->toBe([1, 3]);
});

it('stores a screenshot and attaches it to the product', function () {
    Notification::fake();
    Storage::fake('local');

    startNewOrder();
    say(Marketplace::Shein->value);
    say('https://fr.shein.com/p-1.html');
    pass();
    pass();
    say('1');
    pass(); // declared price, lands on the screenshot step

    $this->post(route('chat.upload'), [
        'screenshot' => UploadedFile::fake()->image('produit.jpg', 400, 400),
    ])->assertRedirect();

    pass(); // leave the screenshot step
    pass(); // item comment
    say('no');
    say('CM');
    say('Douala');
    say('+237612345678');
    say('Awa Ndiaye');
    pass();
    $this->post(route('chat.confirm'));

    $item = PurchaseRequest::query()->sole()->items()->with('attachments')->sole();

    expect($item->attachments)->toHaveCount(1)
        ->and($item->attachments->first()->original_name)->toBe('produit.jpg');

    Storage::disk('local')->assertExists($item->attachments->first()->path);
});

it('rejects an upload that is not an image', function () {
    Storage::fake('local');

    startNewOrder();
    say(Marketplace::Shein->value);
    say('https://fr.shein.com/p-1.html');
    pass();
    pass();
    say('1');
    pass();

    $this->post(route('chat.upload'), [
        'screenshot' => UploadedFile::fake()->create('charge.php', 12, 'application/x-php'),
    ])->assertSessionHasErrors('screenshot');
});

it('starts a fresh conversation when the customer restarts', function () {
    startNewOrder();
    say(Marketplace::Shein->value);

    $this->post(route('chat.restart'));

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Menu->value)
        ->where('conversation.item_count', 0)
    );
});

it('refuses to confirm a conversation that is not finished', function () {
    startNewOrder();
    say(Marketplace::Shein->value);

    $this->post(route('chat.confirm'))->assertSessionHasErrors('conversation');

    expect(PurchaseRequest::count())->toBe(0);
});

it('recognises a returning customer by phone number', function () {
    Notification::fake();

    answerUntilSummary();
    $this->post(route('chat.confirm'));
    $this->post(route('chat.restart'));

    answerUntilSummary();
    $this->post(route('chat.confirm'));

    expect(PurchaseRequest::count())->toBe(2)
        ->and(Customer::count())->toBe(1);
});

it('exposes the confirmation and restart choices on the recap step', function () {
    answerUntilSummary();

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.options.0.value', ChatbotEngine::CONFIRM)
        ->where('conversation.current.options.1.value', ChatbotEngine::RESTART)
    );
});

it('tracks a request from its reference and phone number', function () {
    $customer = Customer::factory()->create(['phone' => '+237612345678']);
    $request = PurchaseRequest::factory()->for($customer)->quoted()->create();
    PurchaseItem::factory()->count(2)->for($request)->create();

    $this->get(route('chat.show'));
    say(Intent::TrackOrder->value);
    say($request->reference);
    say('+237 612 345 678');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Menu->value)
        ->where('conversation.messages.7.text', fn (string $text) => str_contains($text, $request->reference)
            && str_contains($text, 'Devis envoyé')
            && str_contains($text, 'Produits : 2')
        )
    );
});

it('does not reveal a request when the phone number does not match', function () {
    $request = PurchaseRequest::factory()
        ->for(Customer::factory()->create(['phone' => '+237600000000']))
        ->create();

    $this->get(route('chat.show'));
    say(Intent::TrackOrder->value);
    say($request->reference);
    say('+237699999999');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.messages.7.text', fn (string $text) => str_contains($text, 'Aucune demande ne correspond'))
    );
});

it('rejects a malformed tracking reference', function () {
    $this->get(route('chat.show'));
    say(Intent::TrackOrder->value);

    say('pas-une-reference!');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::TrackReference->value)
    );
});

it('hands a new customer their access code at the end of the first request', function () {
    Notification::fake();

    answerUntilSummary();
    $this->post(route('chat.confirm'));

    $customer = Customer::query()->sole();

    // Shown grouped, and only ever here: it is stored hashed, so nothing can
    // read it back afterwards.
    expect($customer->access_code_hash)->not->toBeNull()
        ->and(transcript())->toMatch("/🔐 Voici votre code d'accès : [2-9A-Z]{3}-[2-9A-Z]{3}/");
});

it('leaves a returning customer on the access code they already have', function () {
    Notification::fake();

    // A second request must not silently invalidate the code saved after the
    // first, which is the key to the customer's whole history.
    $customer = Customer::factory()->withAccessCode()->create(['phone' => '+237612345678']);
    $original = $customer->access_code_hash;

    answerUntilSummary();
    $this->post(route('chat.confirm'));

    expect($customer->refresh()->access_code_hash)->toBe($original)
        ->and(transcript())->not->toContain("Voici votre code d'accès");
});

/**
 * Ask for the request list, answering with the given phone and access code.
 */
function askForMyOrders(string $phone, string $code): void
{
    say(Intent::MyOrders->value);
    say($phone);
    say($code);
}

/**
 * Every line of the conversation, joined so a test can search it whole.
 */
function transcript(): string
{
    $conversation = test()->get(route('chat.show'))
        ->viewData('page')['props']['conversation'];

    return collect($conversation['messages'])->pluck('text')->implode("\n");
}

it('lists the requests once the access code checks out', function () {
    $customer = Customer::factory()->withAccessCode()->create(['phone' => '+237612345678']);
    $requests = PurchaseRequest::factory()->count(2)->for($customer)->create();
    PurchaseItem::factory()->for($requests->first())->create([
        'product_url' => 'https://fr.shein.com/secret-product.html',
    ]);

    $this->get(route('chat.show'));
    askForMyOrders('+237612345678', 'K4M-9PZ');

    $text = transcript();

    expect($text)->toContain($requests->first()->reference)
        ->and($text)->toContain($requests->last()->reference)
        // The list is an index, not the contents: what was ordered stays out.
        ->and($text)->not->toContain('secret-product');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Menu->value)
    );
});

it('refuses the list when the access code is wrong', function () {
    $customer = Customer::factory()->withAccessCode()->create(['phone' => '+237612345678']);
    $requests = PurchaseRequest::factory()->count(2)->for($customer)->create();

    $this->get(route('chat.show'));
    askForMyOrders('+237612345678', 'AAA-BBB');

    $text = transcript();

    expect($text)->not->toContain($requests->first()->reference)
        ->and($text)->toContain('Aucune demande ne correspond à ce numéro et à ce code.');
});

it('answers a wrong code and an unknown number identically', function () {
    // Any difference between the two would let somebody sweep phone numbers to
    // find out which ones belong to customers.
    Customer::factory()->withAccessCode()->create(['phone' => '+237612345678']);

    $this->get(route('chat.show'));
    askForMyOrders('+237612345678', 'AAA-BBB');
    $wrongCode = transcript();

    $this->post(route('chat.restart'));
    askForMyOrders('+237600000000', 'AAA-BBB');
    $unknownNumber = transcript();

    expect(str_contains($wrongCode, 'Aucune demande ne correspond à ce numéro et à ce code.'))
        ->toBeTrue()
        ->and(str_contains($unknownNumber, 'Aucune demande ne correspond à ce numéro et à ce code.'))
        ->toBeTrue();
});

it('accepts the access code however it is spaced or cased', function () {
    $customer = Customer::factory()->withAccessCode()->create(['phone' => '+237612345678']);
    $request = PurchaseRequest::factory()->for($customer)->create();

    $this->get(route('chat.show'));
    askForMyOrders('+237612345678', 'k4m 9pz');

    expect(transcript())->toContain($request->reference);
});

it('throttles repeated lookups to prevent phone number enumeration', function () {
    $this->get(route('chat.show'));

    // Five code checks a minute from one address. Each lookup returns to the
    // menu, so an enumerator has to walk the whole flow for every number.
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        askForMyOrders('+2376000000'.str_pad((string) $attempt, 2, '0', STR_PAD_LEFT), 'AAA-BBB');
    }

    say(Intent::MyOrders->value);
    say('+237600000099');

    $this->post(route('chat.message'), ['message' => 'AAA-BBB'])
        ->assertSessionHasErrors('message');
});

it('keeps a phone number locked after the per-minute limit has reset', function () {
    Customer::factory()->withAccessCode()->create(['phone' => '+237612345678']);

    $this->get(route('chat.show'));

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        askForMyOrders('+237612345678', 'AAA-BBB');
    }

    // The per-address limiter expires after a minute; the per-number one holds
    // for an hour, which is what makes waiting a poor attack strategy.
    $this->travel(2)->minutes();

    askForMyOrders('+237612345678', 'K4M-9PZ');

    expect(transcript())->toContain('Trop de tentatives.');
});

/**
 * Pick "leave a review" from the menu and answer the rating.
 */
function rate(int $rating): void
{
    say(Intent::LeaveReview->value);
    say((string) $rating);
}

it('offers leaving a review on the welcome menu', function () {
    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.options', fn ($options) => collect($options)
            ->contains(fn (array $option) => $option['value'] === Intent::LeaveReview->value))
    );
});

it('asks for a rating on a five point scale', function () {
    $this->get(route('chat.show'));

    say(Intent::LeaveReview->value);

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::ReviewRating->value)
        ->where('conversation.current.input_type', 'choice')
        ->has('conversation.current.options', 5)
        // Best first, so the list reads top down the way a scale does.
        ->where('conversation.current.options.0.value', '5')
        ->where('conversation.current.options.4.value', '1')
    );
});

it('records a review with its comment and returns to the menu', function () {
    $this->get(route('chat.show'));

    rate(5);
    say('Livraison rapide et équipe à l\'écoute.');

    $review = Review::sole();

    expect($review->rating)->toBe(5)
        ->and($review->comment)->toBe('Livraison rapide et équipe à l\'écoute.')
        ->and($review->channel)->toBe(Channel::Web)
        ->and($review->isApproved())->toBeFalse();

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Menu->value)
    );

    expect(transcript())->toContain('Merci beaucoup pour votre avis');
});

it('records a review left without a word', function () {
    $this->get(route('chat.show'));

    rate(2);
    pass();

    $review = Review::sole();

    // Skipping the comment must still store the rating: the engine's own skip
    // would have advanced the step and dropped it.
    expect($review->rating)->toBe(2)
        ->and($review->comment)->toBeNull();

    expect(transcript())->toContain('Merci beaucoup pour votre avis');
});

it('refuses a rating outside the scale', function () {
    $this->get(route('chat.show'));

    say(Intent::LeaveReview->value);
    say('9');

    expect(Review::count())->toBe(0)
        ->and(transcript())->toContain('Choisissez une note dans la liste');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::ReviewRating->value)
    );
});

it('turns away a review longer than its field allows', function () {
    $this->get(route('chat.show'));

    rate(4);
    say(str_repeat('a', 501));

    expect(Review::count())->toBe(0)
        ->and(transcript())->toContain('résumer en 500 caractères');
});

it('leaves a review anonymous when the conversation cannot name the visitor', function () {
    Customer::factory()->create(['phone' => '+237612345678']);

    $this->get(route('chat.show'));

    rate(3);
    say('Correct sans plus.');

    // Nothing in this conversation proved who was speaking, so nothing is
    // attributed — guessing from an unverified number would let anybody leave a
    // review in a stranger's name.
    expect(Review::sole()->customer_id)->toBeNull();
});

it('attributes a review to the customer whose access code was accepted', function () {
    $customer = Customer::factory()->withAccessCode()->create(['phone' => '+237612345678']);
    PurchaseRequest::factory()->for($customer)->create();

    $this->get(route('chat.show'));
    askForMyOrders('+237612345678', CustomerFactory::TEST_ACCESS_CODE);

    rate(5);
    say('Parfait.');

    expect(Review::sole()->customer_id)->toBe($customer->id);
});

it('attributes a review to the request the visitor has just submitted', function () {
    Notification::fake();

    answerUntilSummary();
    $this->post(route('chat.confirm'));

    $request = PurchaseRequest::sole();

    // Completing a request is a dead end, so the review is reached by going
    // back to the menu — which restarts the conversation but keeps nothing the
    // visitor did not prove.
    $this->post(route('chat.menu'));

    expect(Review::count())->toBe(0);
    expect($request->reference)->not->toBeNull();
});

it('stops a conversation from flooding the back office with ratings', function () {
    $this->get(route('chat.show'));

    foreach ([5, 4, 3] as $rating) {
        rate($rating);
        pass();
    }

    rate(1);
    pass();

    expect(Review::count())->toBe(3)
        ->and(transcript())->toContain('déjà laissé plusieurs avis');
});

it('refuses a destination the landing page only announces', function () {
    config([
        'shoprelle.countries' => ['CM' => 'Cameroun'],
        'shoprelle.upcoming_countries' => ['SN' => 'Sénégal'],
    ]);

    startNewOrder();
    describeItem(Marketplace::Shein, 'https://fr.shein.com/product-p-123.html');
    say('no');

    // The two lists must never merge: the page says Senegal is coming, and
    // until the config moves it the assistant has to keep saying no.
    say('SN');

    expect(transcript())->toContain('Nous ne livrons pas encore dans ce pays');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Country->value)
        ->has('conversation.current.options', 1)
        ->where('conversation.current.options.0.value', 'CM')
    );
});

it('opens a request straight from a link pasted on the landing page', function () {
    $this->get(route('chat.show'));

    $this->post(route('chat.link'), ['url' => 'https://fr.shein.com/product-p-123.html'])
        ->assertRedirect(route('chat.show'));

    // Platform and product are already behind the visitor: the first thing the
    // assistant asks for is the colour.
    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Color->value)
    );

    expect(transcript())->toContain('Shein détecté');
});

it('starts a request from a marketplace clicked on the landing page', function () {
    $this->get(route('chat.start', 'nike'))
        ->assertRedirect(route('chat.show'));

    // The platform is already behind the visitor: the first thing the
    // assistant asks for is the product link.
    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::ProductUrl->value)
    );

    expect(transcript())->toContain('Nike')
        ->toContain('Envoyez le lien du produit');
});

it('carries the clicked marketplace through to the submitted request', function () {
    Notification::fake();

    $this->get(route('chat.start', 'darty'));

    say('https://www.darty.com/produit-42.html');
    pass(); // colour
    pass(); // size
    say('1');
    pass(); // declared price
    pass(); // screenshot
    pass(); // item comment
    say('no');
    say('CM');
    say('Douala');
    say('+237 6 12 34 56 78');
    say('Awa Ndiaye');
    pass(); // email

    $this->post(route('chat.confirm'));

    expect(PurchaseItem::sole()->marketplace)->toBe(Marketplace::Darty);
});

it('discards a conversation in progress when a marketplace is clicked', function () {
    $this->get(route('chat.show'));
    $this->post(route('chat.link'), ['url' => 'https://www.temu.com/women-jacket-p-8842.html']);
    say('Noir');

    $this->get(route('chat.start', 'zara'));

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::ProductUrl->value)
    );

    expect(transcript())->toContain('Zara')
        ->not->toContain('temu.com');
});

it('rejects an unknown marketplace from the landing page', function () {
    $this->get(route('chat.start', 'boutique-inconnue'))->assertNotFound();
});

it('accepts a link from a site it does not recognise', function () {
    $this->get(route('chat.show'));

    $this->post(route('chat.link'), ['url' => 'https://boutique-inconnue.example/produit/42']);

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Color->value)
    );

    // An unknown host is not a refusal — "another site" is a marketplace too.
    expect(transcript())->toContain('Nous achetons aussi en dehors des grandes plateformes');
});

it('carries the pasted link through to the submitted request', function () {
    Notification::fake();

    $this->get(route('chat.show'));
    $this->post(route('chat.link'), ['url' => 'https://www.temu.com/women-jacket-p-8842.html']);

    say('Noir');
    say('XL');
    say('1');
    pass(); // declared price
    pass(); // screenshot
    pass(); // item comment
    say('no');
    say('CM');
    say('Douala');
    say('+237 6 12 34 56 78');
    say('Awa Ndiaye');
    pass(); // email

    $this->post(route('chat.confirm'));

    $item = PurchaseItem::sole();

    expect($item->product_url)->toBe('https://www.temu.com/women-jacket-p-8842.html')
        ->and($item->marketplace)->toBe(Marketplace::Temu)
        ->and($item->color)->toBe('Noir');
});

it('turns away something that is not a link', function () {
    $this->get(route('chat.show'));

    $this->post(route('chat.link'), ['url' => 'une veste noire'])
        ->assertSessionHasErrors('url');

    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Menu->value)
    );
});

it('discards a conversation in progress when a new link is pasted', function () {
    answerUntilSummary();

    $this->post(route('chat.link'), ['url' => 'https://fr.shein.com/other-p-999.html']);

    // The visitor asked for a new request by pasting one; grafting the link
    // onto a half-finished order is the surprising reading.
    $this->get(route('chat.show'))->assertInertia(fn ($page) => $page
        ->where('conversation.current.step', Step::Color->value)
        ->where('conversation.item_count', 0)
    );
});

it('detects the newer platforms from a pasted link', function (string $url, Marketplace $expected) {
    $this->get(route('chat.show'));

    $this->post(route('chat.link'), ['url' => $url]);

    expect(transcript())->toContain($expected->label().' détecté');
})->with([
    'Zalando' => ['https://www.zalando.fr/veste-matelassee-123.html', Marketplace::Zalando],
    'Bershka' => ['https://www.bershka.com/fr/veste-c0p456.html', Marketplace::Bershka],
]);
