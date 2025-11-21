<?php

namespace App\Http\Controllers\Telegram;

use App\Handlers\Telegram\CallbackQueryHandler;
use App\Handlers\Telegram\ChannelPostHandler;
use App\Handlers\Telegram\UserMessageHandler;
use App\Http\Controllers\Controller;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly ChannelPostHandler $channelPostHandler,
        private readonly UserMessageHandler $userMessageHandler,
        private readonly CallbackQueryHandler $callbackQueryHandler
    ) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $update = $request->all();

            if (isset($update['channel_post'])) {
                $this->channelPostHandler->handle($update['channel_post']);
            }

            if (isset($update['message'])) {
                $this->userMessageHandler->handle($update['message']);
            }

            if (isset($update['callback_query'])) {
                $this->callbackQueryHandler->handle($update['callback_query']);
            }

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('❌ Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function setWebhook(TelegramService $telegram): JsonResponse
    {
        $webhookUrl = config('app.url') . '/api/telegram/webhook';

        $result = $telegram->setWebhook($webhookUrl, [
            'message',
            'callback_query',
            'channel_post'
        ]);

        return response()->json($result);
    }

    public function getWebhookInfo(TelegramService $telegram): JsonResponse
    {
        $response = Http::get("https://api.telegram.org/bot{$telegram->token}/getWebhookInfo");

        return response()->json($response->json());
    }
}
