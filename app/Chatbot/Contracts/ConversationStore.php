<?php

namespace App\Chatbot\Contracts;

use App\Chatbot\Channel;
use App\Chatbot\ChannelConversationStore;
use App\Chatbot\ConversationState;

/**
 * Where an in-progress conversation lives between two customer messages.
 *
 * The key identifies the conversation partner and always takes the form
 * "{channel}:{identifier}" — see {@see Channel::key()}. The prefix
 * is what lets {@see ChannelConversationStore} pick the right
 * backing store.
 */
interface ConversationStore
{
    public function find(string $key): ?ConversationState;

    public function save(string $key, ConversationState $state): void;

    public function forget(string $key): void;
}
