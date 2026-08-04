<?php

use App\Chatbot\Intent;
use App\Chatbot\Step;
use App\Models\ContactMessage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets a visitor write to the team from the assistant', function () {
    $this->get(route('chat.show'));

    $this->post(route('chat.message'), ['message' => Intent::ContactUs->value]);
    $this->post(route('chat.message'), ['message' => 'Livrez-vous jusqu\'à Kribi ?']);
    $this->post(route('chat.message'), ['message' => '+237 6 99 88 77 66']);

    $message = ContactMessage::sole();

    expect($message->message)->toBe('Livrez-vous jusqu\'à Kribi ?')
        ->and($message->reply_to)->toBe('+237 6 99 88 77 66')
        ->and($message->handled_at)->toBeNull();
});

it('accepts a message typed straight into the menu', function () {
    $this->get(route('chat.show'));
    $this->post(route('chat.message'), ['message' => 'Je cherche une pièce détachée introuvable ici.']);

    // Le menu a compris qu'on lui écrivait : il ne redemande pas le message,
    // il demande seulement comment répondre.
    $this->get(route('chat.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.current.step', Step::ContactReply->value)
        );

    $this->post(route('chat.skip'));

    expect(ContactMessage::sole()->message)
        ->toBe('Je cherche une pièce détachée introuvable ici.');
});

it('keeps a message that has no way back', function () {
    $this->get(route('chat.show'));
    $this->post(route('chat.message'), ['message' => Intent::ContactUs->value]);
    $this->post(route('chat.message'), ['message' => 'Une question rapide sur les délais.']);
    $this->post(route('chat.skip'));

    expect(ContactMessage::sole()->reply_to)->toBeNull();
});

it('refuses a message too short to transmit', function () {
    $this->get(route('chat.show'));
    $this->post(route('chat.message'), ['message' => Intent::ContactUs->value]);
    $this->post(route('chat.message'), ['message' => 'hm']);

    expect(ContactMessage::count())->toBe(0);
});

it('shows the messages to an administrator', function () {
    ContactMessage::factory()->create(['message' => 'Livrez-vous à Kribi ?']);
    ContactMessage::factory()->handled()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.messages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/messages/index')
            ->has('messages', 2)
            ->where('pending', 1)
            // Le plus ancien non traité en tête : une file se traite dans
            // l'ordre où elle s'est formée.
            ->where('messages.0.message', 'Livrez-vous à Kribi ?')
        );
});

it('keeps the messages away from a guest', function () {
    $this->get(route('admin.messages.index'))->assertRedirect(route('login'));
});

it('marks a message as handled, and back', function () {
    $admin = User::factory()->admin()->create();
    $message = ContactMessage::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.messages.update', $message))
        ->assertRedirect();

    expect($message->refresh()->handled_at)->not->toBeNull()
        ->and($message->handled_by)->toBe($admin->id);

    $this->actingAs($admin)->patch(route('admin.messages.update', $message));

    expect($message->refresh()->handled_at)->toBeNull()
        ->and($message->handled_by)->toBeNull();
});
