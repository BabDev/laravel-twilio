<?php

namespace BabDev\Twilio\Tests;

use BabDev\Twilio\ConnectionManager;
use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use BabDev\Twilio\Providers\TwilioProvider;
use BabDev\Twilio\TwilioClient;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;

final class ConnectionManagerTest extends TestCase
{
    public function testTheDefaultConnectionIsCreated(): void
    {
        $this->assertInstanceOf(TwilioClient::class, $this->app->make(ConnectionManager::class)->connection());
    }

    public function testACustomConnectionIsCreated(): void
    {
        /** @var ConnectionManager $manager */
        $manager = $this->app->make(ConnectionManager::class);

        $this->assertInstanceOf(TwilioClient::class, $manager->connection('custom'));
        $this->assertNotSame($manager->connection(), $manager->connection('custom'), 'The default manager instance should not be the same as the custom instance.');
    }

    public function testAnUnknownCustomConnectionCausesAnException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->app->make(ConnectionManager::class)->connection('does_not_exist');
    }

    public function testRetrievingTheSdkClientProxiesThrough(): void
    {
        /** @var Stub&Client $twilioClient */
        $twilioClient = $this->createStub(Client::class);

        /** @var MockObject&TwilioClientContract $client */
        $client = $this->createMock(TwilioClientContract::class);
        $client->expects($this->once())
            ->method('twilio')
            ->willReturn($twilioClient);

        /** @var ConnectionManager $manager */
        $manager = $this->app->make(ConnectionManager::class);
        $manager->extend(
            'twilio',
            static fn(Container $container): TwilioClientContract => $client
        );

        $this->assertSame($twilioClient, $manager->twilio());
    }

    public function testPlacingACallProxiesThrough(): void
    {
        /** @var Stub&CallInstance $call */
        $call = $this->createStub(CallInstance::class);

        /** @var MockObject&TwilioClientContract $client */
        $client = $this->createMock(TwilioClientContract::class);
        $client->expects($this->once())
            ->method('call')
            ->willReturn($call);

        /** @var ConnectionManager $manager */
        $manager = $this->app->make(ConnectionManager::class);
        $manager->extend(
            'twilio',
            static fn(Container $container): TwilioClientContract => $client
        );

        $this->assertSame($call, $manager->call('me', []));
    }

    public function testSendingAMessageProxiesThrough(): void
    {
        /** @var Stub&MessageInstance $message */
        $message = $this->createStub(MessageInstance::class);

        /** @var MockObject&TwilioClientContract $client */
        $client = $this->createMock(TwilioClientContract::class);
        $client->expects($this->once())
            ->method('message')
            ->willReturn($message);

        /** @var ConnectionManager $manager */
        $manager = $this->app->make(ConnectionManager::class);

        $manager->extend(
            'twilio',
            static fn(Container $container): TwilioClientContract => $client
        );

        $this->assertSame($message, $manager->message('me', 'Hello!', []));
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup connections configuration
        $app['config']->set(
            'twilio.connections.twilio',
            [
                'sid' => 'api_sid',
                'token' => 'api_token',
                'from' => '+15558675309',
            ]
        );

        $app['config']->set(
            'twilio.connections.custom',
            [
                'sid' => 'custom_sid',
                'token' => 'custom_token',
                'from' => '+15558675309',
            ]
        );
    }

    /**
     * @return class-string<ServiceProvider>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TwilioProvider::class,
        ];
    }
}
