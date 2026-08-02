<?php

namespace App\Chatbot;

use App\Chatbot\Contracts\ConversationStore;
use Illuminate\Support\Facades\Session;

/**
 * Keeps web conversations in the visitor's session.
 *
 * The session is the right home for a browser conversation: it lives as long as
 * the visitor does, and flushing the cache cannot wipe a request someone is in
 * the middle of writing. Channels without a session (Telegram, WhatsApp) use
 * {@see CacheConversationStore} instead.
 */
class SessionConversationStore implements ConversationStore
{
    public function find(string $key): ?ConversationState
    {
        $payload = Session::get($this->sessionKey($key));

        return is_array($payload) ? ConversationState::fromArray($payload) : null;
    }

    public function save(string $key, ConversationState $state): void
    {
        Session::put($this->sessionKey($key), $state->toArray());
    }

    public function forget(string $key): void
    {
        Session::forget($this->sessionKey($key));
    }

    private function sessionKey(string $key): string
    {
        return config('shoprelle.chatbot.session_key').'.'.sha1($key);
    }
}
