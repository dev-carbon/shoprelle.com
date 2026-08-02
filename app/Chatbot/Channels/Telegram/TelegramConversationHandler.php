<?php

namespace App\Chatbot\Channels\Telegram;

use App\Chatbot\Channel;
use App\Chatbot\ChatbotEngine;
use App\Chatbot\ChatMessage;
use App\Chatbot\ConversationManager;
use App\Chatbot\ConversationState;
use App\Chatbot\Step;
use App\Exceptions\ConversationException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;

/**
 * Drives a Shoprelle conversation over Telegram.
 *
 * Everything Telegram-specific stops here: parsing an update, answering a
 * button press, downloading a photo and choosing a keyboard. The conversation
 * itself is the shared engine, reached through {@see ConversationManager}.
 */
class TelegramConversationHandler
{
    public function __construct(
        private ConversationManager $conversations,
        private ChatbotEngine $engine,
        private TelegramClient $client,
        private TelegramKeyboard $keyboard,
        private Cache $cache,
    ) {}

    /**
     * React to one update. Never throws: the webhook must always answer 200, or
     * Telegram retries the same update indefinitely.
     */
    public function handle(TelegramUpdate $update): void
    {
        if ($this->alreadyHandled($update)) {
            return;
        }

        try {
            $this->dispatch($update);
        } catch (ConversationException $exception) {
            $this->client->sendMessage($update->chatId, $exception->getMessage());
        } catch (\Throwable $exception) {
            Log::error('Telegram update handling failed.', [
                'update_id' => $update->updateId,
                'chat_id' => $update->chatId,
                'exception' => $exception,
            ]);

            $this->client->sendMessage(
                $update->chatId,
                'Une erreur est survenue de notre côté. Envoyez /menu pour reprendre.',
            );
        }
    }

    private function dispatch(TelegramUpdate $update): void
    {
        $key = Channel::Telegram->key((string) $update->chatId);

        if ($update->callbackQueryId !== null) {
            $this->client->answerCallbackQuery($update->callbackQueryId);
        }

        $before = $this->conversations->current($key);

        $state = match (true) {
            $update->command() !== null => $this->handleCommand($key, (string) $update->command()),
            $update->isCallback() => $this->handleAnswer($key, (string) $update->callbackData),
            $update->hasPhoto() => $this->handlePhoto($key, (string) $update->photoFileId),
            $update->text !== null => $this->handleAnswer($key, $update->text),
            default => $this->unsupportedContent($key),
        };

        $this->reply($update->chatId, $state, $before);
    }

    /**
     * Slash commands are the escape hatch: they work at any point in the flow.
     */
    private function handleCommand(string $key, string $command): ConversationState
    {
        return match ($command) {
            'start' => $this->conversations->restart($key),
            'menu', 'annuler' => $this->conversations->backToMenu($key),
            'aide', 'help' => $this->conversations->reply($key, 'help'),
            default => $this->unknownCommand($key),
        };
    }

    private function handleAnswer(string $key, string $input): ConversationState
    {
        return match ($input) {
            TelegramKeyboard::SKIP => $this->conversations->skip($key),
            TelegramKeyboard::MENU => $this->conversations->backToMenu($key),
            default => $this->conversations->reply($key, $input),
        };
    }

    /**
     * Download the photo the customer sent and attach it to the current item.
     */
    private function handlePhoto(string $key, string $fileId): ConversationState
    {
        $path = $this->client->filePath($fileId);
        $contents = $path === null ? null : $this->client->download($path);

        if ($contents === null) {
            throw ConversationException::uploadFailed();
        }

        return $this->conversations->uploadContents(
            $key,
            $contents,
            basename($path) ?: 'capture.jpg',
        );
    }

    private function unknownCommand(string $key): ConversationState
    {
        $state = $this->conversations->current($key);
        $state->pushBotMessage('Je ne connais pas cette commande. Utilisez /menu pour revenir au menu, ou /aide.');

        return $state;
    }

    private function unsupportedContent(string $key): ConversationState
    {
        $state = $this->conversations->current($key);
        $state->pushBotMessage('Je ne sais traiter que du texte, des boutons et des photos.');

        return $state;
    }

    /**
     * Send everything the bot said since the customer's message, attaching the
     * keyboard to the last one so the buttons sit under the latest question.
     *
     * /start replaces the conversation outright, so the transcript is compared
     * by conversation id: a new one is sent in full rather than diffed against
     * the transcript it replaced.
     */
    private function reply(int $chatId, ConversationState $state, ConversationState $before): void
    {
        $alreadySent = $state->id === $before->id ? count($before->messages) : 0;
        $texts = [];

        foreach (array_slice($state->messages, $alreadySent) as $message) {
            if ($message->author === ChatMessage::BOT) {
                $texts[] = $message->text;
            }
        }

        // The web renders the recap as a card; here it has to be spelled out,
        // right before the question asking the customer to confirm it.
        if ($state->step === Step::Summary && $texts !== []) {
            array_splice($texts, count($texts) - 1, 0, [$this->engine->summaryText($state)]);
        }

        if ($texts === []) {
            return;
        }

        $keyboard = $this->keyboard->for(
            $this->engine->describe($state),
            count($state->draft['attachments'] ?? []),
        );

        $last = array_key_last($texts);

        foreach ($texts as $index => $text) {
            $this->client->sendMessage($chatId, $text, $index === $last ? $keyboard : null);
        }
    }

    /**
     * Telegram redelivers an update until it is acknowledged, and a slow reply
     * can produce a duplicate. Remembering the id keeps one button press from
     * being applied twice.
     */
    private function alreadyHandled(TelegramUpdate $update): bool
    {
        $key = 'telegram:update:'.$update->updateId;

        if ($this->cache->has($key)) {
            return true;
        }

        $this->cache->put($key, true, now()->addHour());

        return false;
    }
}
