<?php

namespace App\Console\Commands;

use App\Chatbot\Channels\Telegram\TelegramClient;
use Illuminate\Console\Command;

/**
 * Registers, inspects or removes the Telegram webhook.
 *
 * Telegram will only call an HTTPS endpoint, so pointing it at a local machine
 * means exposing it through a tunnel first.
 */
class TelegramWebhook extends Command
{
    protected $signature = 'shoprelle:telegram-webhook
                            {action=info : set, delete or info}
                            {--url= : Public HTTPS URL of the webhook, defaults to the app URL}';

    protected $description = 'Manage the Telegram webhook registration';

    public function handle(TelegramClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->components->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        return match ($this->argument('action')) {
            'set' => $this->set($client),
            'delete' => $this->delete($client),
            'info' => $this->info_($client),
            default => $this->invalidAction(),
        };
    }

    private function set(TelegramClient $client): int
    {
        $secret = config('services.telegram.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            $this->components->error(
                'TELEGRAM_WEBHOOK_SECRET is not set. Generate one, for example: php -r "echo bin2hex(random_bytes(24));"',
            );

            return self::FAILURE;
        }

        $url = $this->option('url') ?: route('telegram.webhook');

        if (! str_starts_with($url, 'https://')) {
            $this->components->error("Telegram only accepts HTTPS webhooks. Got: {$url}");
            $this->components->info('Expose your local server with a tunnel, then pass --url=https://…/telegram/webhook');

            return self::FAILURE;
        }

        if ($client->setWebhook($url, $secret) === null) {
            $this->components->error('Telegram refused the webhook registration. Check the logs.');

            return self::FAILURE;
        }

        $this->components->info("Webhook registered: {$url}");

        return self::SUCCESS;
    }

    private function delete(TelegramClient $client): int
    {
        if ($client->deleteWebhook() === null) {
            $this->components->error('Could not delete the webhook. Check the logs.');

            return self::FAILURE;
        }

        $this->components->info('Webhook deleted.');

        return self::SUCCESS;
    }

    /**
     * Named with a trailing underscore because Command::info() is taken.
     */
    private function info_(TelegramClient $client): int
    {
        $bot = $client->me();
        $webhook = $client->webhookInfo();

        if ($bot === null || $webhook === null) {
            $this->components->error('Could not reach the Telegram API. Check the logs.');

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Bot', '@'.($bot['username'] ?? 'inconnu'));
        $this->components->twoColumnDetail('URL', $webhook['url'] ?: 'aucune');
        $this->components->twoColumnDetail('Updates en attente', (string) ($webhook['pending_update_count'] ?? 0));

        if (! empty($webhook['last_error_message'])) {
            $this->components->warn('Dernière erreur : '.$webhook['last_error_message']);
        }

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->components->error('Unknown action. Use: set, delete or info.');

        return self::FAILURE;
    }
}
