<?php

namespace App\Chatbot\Channels\Telegram;

/**
 * The parts of a Telegram update Shoprelle reacts to.
 *
 * Parsing happens once, here, so the handler never digs through raw arrays and
 * a malformed payload is rejected at the door.
 */
final readonly class TelegramUpdate
{
    private function __construct(
        public int $updateId,
        public int $chatId,
        public ?string $text,
        public ?string $callbackData,
        public ?string $callbackQueryId,
        public ?string $photoFileId,
    ) {}

    /**
     * Build an update from a decoded webhook payload, or null when it is not
     * something we can act on.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function tryFrom(array $payload): ?self
    {
        $updateId = $payload['update_id'] ?? null;

        if (! is_int($updateId)) {
            return null;
        }

        $callback = $payload['callback_query'] ?? null;

        if (is_array($callback)) {
            $chatId = $callback['message']['chat']['id'] ?? null;

            if (! is_int($chatId)) {
                return null;
            }

            return new self(
                updateId: $updateId,
                chatId: $chatId,
                text: null,
                callbackData: is_string($callback['data'] ?? null) ? $callback['data'] : null,
                callbackQueryId: is_string($callback['id'] ?? null) ? $callback['id'] : null,
                photoFileId: null,
            );
        }

        $message = $payload['message'] ?? null;

        if (! is_array($message)) {
            return null;
        }

        $chatId = $message['chat']['id'] ?? null;

        if (! is_int($chatId)) {
            return null;
        }

        return new self(
            updateId: $updateId,
            chatId: $chatId,
            text: is_string($message['text'] ?? null) ? $message['text'] : null,
            callbackData: null,
            callbackQueryId: null,
            photoFileId: self::largestPhoto($message),
        );
    }

    public function isCallback(): bool
    {
        return $this->callbackData !== null;
    }

    public function hasPhoto(): bool
    {
        return $this->photoFileId !== null;
    }

    /**
     * The slash command in the message, without its argument or @botname part.
     */
    public function command(): ?string
    {
        if ($this->text === null || ! str_starts_with($this->text, '/')) {
            return null;
        }

        $command = strtolower(strtok(substr($this->text, 1), ' ') ?: '');

        return strstr($command, '@', true) ?: $command;
    }

    /**
     * Telegram sends every rendition of a photo, smallest first. The last one
     * is the highest resolution, which is what the administrator wants to see.
     *
     * @param  array<string, mixed>  $message
     */
    private static function largestPhoto(array $message): ?string
    {
        $photos = $message['photo'] ?? null;

        if (! is_array($photos) || $photos === []) {
            return null;
        }

        $largest = end($photos);
        $fileId = is_array($largest) ? ($largest['file_id'] ?? null) : null;

        return is_string($fileId) ? $fileId : null;
    }
}
