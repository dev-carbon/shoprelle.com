<?php

namespace App\Http\Controllers\Telegram;

use App\Chatbot\Channels\Telegram\TelegramConversationHandler;
use App\Chatbot\Channels\Telegram\TelegramUpdate;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private TelegramConversationHandler $handler,
    ) {}

    /**
     * Receive one Telegram update.
     *
     * Always answers 200, even for a payload we cannot use: any other status
     * makes Telegram redeliver the same update on a growing backoff, which
     * would eventually stall the bot for every customer.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $update = TelegramUpdate::tryFrom($request->all());

        if ($update !== null) {
            $this->handler->handle($update);
        }

        return response()->json(['ok' => true]);
    }
}
