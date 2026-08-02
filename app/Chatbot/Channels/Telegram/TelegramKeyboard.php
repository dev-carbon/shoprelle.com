<?php

namespace App\Chatbot\Channels\Telegram;

use App\Chatbot\InputType;

/**
 * Turns the engine's step descriptor into a Telegram inline keyboard.
 *
 * This is the whole of the Telegram-specific presentation: the flow, the
 * wording and the validation all come from the shared engine. Adding a step to
 * the conversation needs no change here.
 */
class TelegramKeyboard
{
    /**
     * Callback payloads for the actions that are not step answers.
     *
     * Telegram caps callback_data at 64 bytes, so these stay short.
     */
    public const SKIP = '__skip__';

    public const MENU = '__menu__';

    /**
     * @param  array{input_type: string, optional: bool, options: list<array{value: string, label: string}>}  $step
     * @return array<string, mixed>|null
     */
    public function for(array $step, int $attachmentCount = 0): ?array
    {
        $rows = match (InputType::from($step['input_type'])) {
            InputType::Choice => $this->choiceRows($step['options']),
            InputType::File => $this->fileRows($attachmentCount),
            InputType::None => [[$this->button('🛒 Nouvelle demande', self::MENU)]],
            default => $step['optional'] ? [[$this->button('Passer', self::SKIP)]] : [],
        };

        if ($rows === []) {
            return null;
        }

        return ['inline_keyboard' => $rows];
    }

    /**
     * One button per option, two per row so long labels stay readable on a
     * phone. Two-option steps keep a single row.
     *
     * @param  list<array{value: string, label: string}>  $options
     * @return list<list<array{text: string, callback_data: string}>>
     */
    private function choiceRows(array $options): array
    {
        if (count($options) <= 2) {
            return [array_map(fn (array $option): array => $this->button(
                $option['label'],
                $option['value'],
            ), $options)];
        }

        $rows = [];

        foreach (array_chunk($options, 2) as $chunk) {
            $rows[] = array_map(fn (array $option): array => $this->button(
                $option['label'],
                $option['value'],
            ), $chunk);
        }

        return $rows;
    }

    /**
     * @return list<list<array{text: string, callback_data: string}>>
     */
    private function fileRows(int $attachmentCount): array
    {
        return [[
            $this->button(
                $attachmentCount > 0 ? 'Continuer' : 'Passer cette étape',
                self::SKIP,
            ),
        ]];
    }

    /**
     * @return array{text: string, callback_data: string}
     */
    private function button(string $label, string $value): array
    {
        return [
            'text' => $label,
            // Values come from our own enums and are always well under the
            // 64-byte cap, but truncate defensively rather than let Telegram
            // reject the whole message.
            'callback_data' => mb_strcut($value, 0, 64),
        ];
    }
}
