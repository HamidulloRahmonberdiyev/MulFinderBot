<?php

use App\Http\Controllers\Api\FilmController;
use App\Http\Controllers\Api\Searchcontroller;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('telegram')->group(function () {
  Route::post('/webhook', [TelegramWebhookController::class, 'handle']);
  Route::get('/set-webhook', [TelegramWebhookController::class, 'setWebhook']);
  Route::get('/webhook-info', [TelegramWebhookController::class, 'getWebhookInfo']);
  Route::post('/search', [TelegramWebhookController::class, 'searchAndSend']);
});

Route::prefix('stories')->name('stories.')->group(function () {
  Route::get('/', [StoryController::class, 'index'])->name('index');
  Route::get('/{id}', [StoryController::class, 'show'])->name('show');
  Route::post('/increment-views', [StoryController::class, 'incrementViews'])->name('increment-views');
  Route::post('/increment-likes', [StoryController::class, 'incrementLikes'])->name('increment-likes');
});

Route::get('search/films', [Searchcontroller::class, 'search'])->name('films.search');

Route::prefix('films')->name('films.')->group(function () {
  Route::get('/', [FilmController::class, 'index'])->name('index');
  Route::get('/{id}', [FilmController::class, 'show'])->name('show');
});
