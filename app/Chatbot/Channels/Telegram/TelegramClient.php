<?php

namespace App\Chatbot\Channels\Telegram;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * A thin wrapper over the Telegram Bot API.
 *
 * Only the handful of methods Shoprelle actually uses. Failures are logged and
 * reported through the return value rather than thrown: a bot that cannot send
 * one message must not take the webhook down, or Telegram will retry forever.
 */
class TelegramClient
{
    public function isConfigured(): bool
    {
        return is_string(config('services.telegram.token'))
            && config('services.telegram.token') !== '';
    }

    /**
     * Send a message, optionally with an inline keyboard.
     *
     * Text is sent unformatted on purpose: the conversation contains customer
     * input and characters like _ * [ ` that Markdown would choke on.
     *
     * @param  array<string, mixed>|null  $replyMarkup
     */
    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): bool
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $this->truncate($text),
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        return $this->call('sendMessage', $payload) !== null;
    }

    /**
     * Acknowledge a button press so Telegram stops showing its spinner.
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): bool
    {
        return $this->call('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ])) !== null;
    }

    /**
     * Resolve a file id into a downloadable path.
     */
    public function filePath(string $fileId): ?string
    {
        $result = $this->call('getFile', ['file_id' => $fileId]);

        $path = $result['file_path'] ?? null;

        return is_string($path) ? $path : null;
    }

    /**
     * Download a file's contents, refusing anything larger than the configured
     * limit so a hostile update cannot exhaust memory.
     */
    public function download(string $filePath): ?string
    {
        $token = $this->token();
        $url = sprintf('%s/file/bot%s/%s', $this->apiUrl(), $token, $filePath);

        try {
            $response = $this->request()->get($url);
        } catch (RuntimeException $exception) {
            Log::warning('Telegram file download failed.', ['exception' => $exception->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Telegram file download failed.', ['status' => $response->status()]);

            return null;
        }

        $contents = $response->body();
        $max = (int) config('services.telegram.max_photo_bytes');

        if (strlen($contents) > $max) {
            Log::warning('Telegram file exceeds the configured size limit.', [
                'bytes' => strlen($contents),
            ]);

            return null;
        }

        return $contents;
    }

    /**
     * Point Telegram at our webhook.
     *
     * @return array<string, mixed>|null
     */
    public function setWebhook(string $url, string $secret): ?array
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            // Shoprelle only reacts to messages and button presses.
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function deleteWebhook(): ?array
    {
        return $this->call('deleteWebhook', ['drop_pending_updates' => true]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function webhookInfo(): ?array
    {
        return $this->call('getWebhookInfo');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function me(): ?array
    {
        return $this->call('getMe');
    }

    /**
     * Call a Bot API method, returning its `result` or null on failure.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function call(string $method, array $payload = []): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('Telegram bot token is not configured.', ['method' => $method]);

            return null;
        }

        $url = sprintf('%s/bot%s/%s', $this->apiUrl(), $this->token(), $method);

        try {
            $response = $this->request()->post($url, $payload);
        } catch (RuntimeException $exception) {
            Log::warning('Telegram API call failed.', [
                'method' => $method,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($response->failed() || $response->json('ok') !== true) {
            Log::warning('Telegram API returned an error.', [
                'method' => $method,
                'status' => $response->status(),
                'description' => $response->json('description'),
            ]);

            return null;
        }

        $result = $response->json('result');

        return is_array($result) ? $result : [];
    }

    private function request(): PendingRequest
    {
        return Http::timeout((int) config('services.telegram.timeout'))
            ->retry(2, 200, throw: false)
            ->asJson();
    }

    private function token(): string
    {
        return (string) config('services.telegram.token');
    }

    private function apiUrl(): string
    {
        return rtrim((string) config('services.telegram.api_url'), '/');
    }

    /**
     * Telegram rejects messages above 4096 characters.
     */
    private function truncate(string $text): string
    {
        return mb_strlen($text) > 4096 ? mb_substr($text, 0, 4093).'…' : $text;
    }
}
