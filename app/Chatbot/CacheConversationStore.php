<?php

namespace App\Chatbot;

use App\Chatbot\Contracts\ConversationStore;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Keeps conversations in the cache, expiring them after a period of inactivity.
 *
 * Intended for channels that have no HTTP session of their own — a Telegram or
 * WhatsApp webhook, where the key is a chat id or a phone number. The web
 * channel uses {@see SessionConversationStore}.
 */
class CacheConversationStore implements ConversationStore
{
    public function __construct(
        private Cache $cache,
    ) {}

    public function find(string $key): ?ConversationState
    {
        $payload = $this->cache->get($this->cacheKey($key));

        return is_array($payload) ? ConversationState::fromArray($payload) : null;
    }

    public function save(string $key, ConversationState $state): void
    {
        $this->cache->put(
            $this->cacheKey($key),
            $state->toArray(),
            now()->addMinutes((int) config('shoprelle.chatbot.idle_timeout_minutes')),
        );
    }

    public function forget(string $key): void
    {
        $this->cache->forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return config('shoprelle.chatbot.session_key').':'.sha1($key);
    }
}
