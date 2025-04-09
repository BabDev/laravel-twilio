<?php

namespace BabDev\Twilio\Providers;

use BabDev\Twilio\ConnectionManager;
use BabDev\Twilio\Contracts\TwilioClient;
use BabDev\Twilio\Notifications\Channels\TwilioChannel;
use BabDev\Twilio\Twilio\Http\LaravelHttpClient;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Http\Client\Factory;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Twilio\Http\Client as TwilioHttpClient;
use Twilio\Http\CurlClient;

final class TwilioProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @return array<string|class-string>
     */
    public function provides(): array
    {
        return [
            ConnectionManager::class,
            TwilioClient::class,

            TwilioHttpClient::class,
        ];
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/twilio.php' => $this->app->configPath('twilio.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->registerConnectionManager();
        $this->registerHttpClient();
        $this->registerNotificationChannel();

        $this->mergeConfigFrom(__DIR__ . '/../../config/twilio.php', 'twilio');
    }

    private function registerConnectionManager(): void
    {
        $this->app->singleton(
            ConnectionManager::class,
            static fn(Application $app): ConnectionManager => new ConnectionManager($app),
        );

        $this->app->alias(ConnectionManager::class, TwilioClient::class);
    }

    private function registerHttpClient(): void
    {
        $this->app->bind(
            TwilioHttpClient::class,
            static fn(Application $app): TwilioHttpClient => new LaravelHttpClient($app->make(Factory::class)),
        );
    }

    private function registerNotificationChannel(): void
    {
        Notification::resolved(static function (ChannelManager $manager): void {
            $manager->extend('twilio', static function (Application $app): TwilioChannel {
                /** @var Repository $config */
                $config = $app->make('config');

                /** @var ConnectionManager $manager */
                $manager = $app->make(ConnectionManager::class);

                return new TwilioChannel(
                    $manager->connection(
                        $config->get('twilio.notification_channel', $config->get('twilio.default', 'twilio')),
                    ),
                );
            });
        });
    }
}
