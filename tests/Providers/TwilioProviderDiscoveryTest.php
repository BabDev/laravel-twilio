<?php

namespace BabDev\Twilio\Tests\Providers;

use BabDev\Twilio\Notifications\Channels\TwilioChannel;
use BabDev\Twilio\Providers\TwilioProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Notifications\ChannelManager;
use Orchestra\Testbench\TestCase;

/**
 * Loads the provider through the same repository used for package discovery, which honors deferred providers,
 * unlike Testbench's package provider registration.
 */
final class TwilioProviderDiscoveryTest extends TestCase
{
    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifestPath = sys_get_temp_dir() . '/' . uniqid('twilio-provider-manifest-', true) . '.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->manifestPath);

        parent::tearDown();
    }

    public function testNotificationChannelIsAvailableWithoutResolvingTheClientFirst(): void
    {
        (new ProviderRepository($this->app, new Filesystem(), $this->manifestPath))->load([TwilioProvider::class]);

        $this->app['config']->set(
            'twilio.connections.twilio',
            [
                'sid' => 'api_sid',
                'token' => 'api_token',
                'from' => '+15558675309',
            ]
        );

        $this->assertInstanceOf(TwilioChannel::class, $this->app->make(ChannelManager::class)->driver('twilio'));
    }
}
