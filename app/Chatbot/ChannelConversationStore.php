<?php

namespace App\Chatbot;

use App\Chatbot\Contracts\ConversationStore;

/**
 * Routes a conversation to the storage its channel can actually use.
 *
 * Browser visitors get the session, which is tied to them and survives a cache
 * flush. Webhook channels have no session at all, so they get the cache, keyed
 * by their chat id.
 *
 * Callers never choose: they hand over a key of the form "{channel}:{id}" and
 * this decides. A new channel only has to pick a prefix.
 */
class ChannelConversationStore implements ConversationStore
{
    public function __construct(
        private SessionConversationStore $session,
        private CacheConversationStore $cache,
    ) {}

    public function find(string $key): ?ConversationState
    {
        return $this->storeFor($key)->find($key);
    }

    public function save(string $key, ConversationState $state): void
    {
        $this->storeFor($key)->save($key, $state);
    }

    public function forget(string $key): void
    {
        $this->storeFor($key)->forget($key);
    }

    private function storeFor(string $key): ConversationStore
    {
        return Channel::of($key) === Channel::Web
            ? $this->session
            : $this->cache;
    }
}
