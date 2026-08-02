<?php

namespace App\Chatbot;

use Illuminate\Support\Str;

/**
 * A single line of the conversation transcript.
 */
final readonly class ChatMessage
{
    public const BOT = 'bot';

    public const CUSTOMER = 'customer';

    private function __construct(
        public string $id,
        public string $author,
        public string $text,
        public string $at,
    ) {}

    public static function fromBot(string $text): self
    {
        return new self(Str::uuid()->toString(), self::BOT, $text, now()->toIso8601String());
    }

    public static function fromCustomer(string $text): self
    {
        return new self(Str::uuid()->toString(), self::CUSTOMER, $text, now()->toIso8601String());
    }

    /**
     * @param  array{id: string, author: string, text: string, at: string}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            id: $attributes['id'],
            author: $attributes['author'],
            text: $attributes['text'],
            at: $attributes['at'],
        );
    }

    /**
     * @return array{id: string, author: string, text: string, at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'text' => $this->text,
            'at' => $this->at,
        ];
    }
}
