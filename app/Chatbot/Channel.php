<?php

namespace App\Chatbot;

/**
 * The medium a conversation is happening over.
 *
 * The value is stored on every purchase request, so the back office can tell
 * where a demand came from, and it prefixes every conversation key.
 */
enum Channel: string
{
    case Web = 'web';
    case Telegram = 'telegram';

    /**
     * Build the conversation key identifying one person on this channel.
     *
     * The web has a single key because the visitor's own session already
     * isolates them; webhook channels key by the chat they are talking in.
     */
    public function key(string $identifier = 'default'): string
    {
        return $this->value.':'.$identifier;
    }

    /**
     * The channel a conversation key belongs to, defaulting to the web for
     * anything unrecognised.
     */
    public static function of(string $key): self
    {
        $prefix = str_contains($key, ':') ? strstr($key, ':', true) : $key;

        return self::tryFrom((string) $prefix) ?? self::Web;
    }

    /**
     * Who the conversation key designates on its channel — a Telegram chat id,
     * say. Null for the web, whose single key names nobody: the visitor is only
     * ever identified by the session the conversation is stored in.
     */
    public static function identifierOf(string $key): ?string
    {
        $identifier = explode(':', $key, 2)[1] ?? '';

        return $identifier === '' || $identifier === 'default' ? null : $identifier;
    }

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Site web',
            self::Telegram => 'Telegram',
        };
    }
}
