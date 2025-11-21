<?php

namespace App\Providers;

use App\Handlers\Telegram\CallbackQueryHandler;
use App\Handlers\Telegram\ChannelPostHandler;
use App\Handlers\Telegram\UserMessageHandler;
use App\Services\Film\FilmParserService;
use App\Services\Film\FilmService;
use App\Services\Message\MessageFormatterService;
use App\Services\TelegramService;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelegramService::class, function ($app) {
            return new TelegramService(
                token: config('telegram.bot_token')
            );
        });

        $this->app->singleton(FilmParserService::class);
        $this->app->singleton(FilmService::class);
        $this->app->singleton(MessageFormatterService::class);

        $this->app->singleton(ChannelPostHandler::class, function ($app) {
            return new ChannelPostHandler(
                parser: $app->make(FilmParserService::class),
                filmService: $app->make(FilmService::class),
                channelId: config('telegram.channel_id')
            );
        });

        $this->app->singleton(UserMessageHandler::class, function ($app) {
            return new UserMessageHandler(
                telegram: $app->make(TelegramService::class),
                filmService: $app->make(FilmService::class),
                formatter: $app->make(MessageFormatterService::class)
            );
        });

        $this->app->singleton(CallbackQueryHandler::class, function ($app) {
            return new CallbackQueryHandler(
                telegram: $app->make(TelegramService::class),
                filmService: $app->make(FilmService::class),
                formatter: $app->make(MessageFormatterService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
