<?php

use App\Http\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('telegram')->group(function () {
  Route::post('/webhook', [TelegramWebhookController::class, 'handle']);
  Route::get('/set-webhook', [TelegramWebhookController::class, 'setWebhook']);
  Route::get('/webhook-info', [TelegramWebhookController::class, 'getWebhookInfo']);
});
