<?php

namespace BabDev\Twilio\Tests;

use BabDev\Twilio\ConnectionManager;
use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use BabDev\Twilio\Providers\TwilioProvider;
use BabDev\Twilio\TwilioClient;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
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

    public function testOptionalConnectionSettingsArePassedToTheSdkClient(): void
    {
        $twilio = $this->app->make(ConnectionManager::class)->connection('api_key')->twilio();

        $this->assertSame('SK123', $twilio->getUsername());
        $this->assertSame('AC123', $twilio->getAccountSid());
        $this->assertSame('ie1', $twilio->getRegion());
        $this->assertSame('dublin', $twilio->getEdge());
    }

    public function testConnectionsWithoutOptionalSettingsUseTheSdkDefaults(): void
    {
        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            '*' => $factory->response(['sid' => 'SM123'], 201),
        ]);

        // The SDK's region and edge getters cannot return null, so check where the request is sent instead
        $this->app->make(ConnectionManager::class)->connection()->message('+15558675310', 'Hello');

        $factory->assertSent(
            static fn(Request $request): bool => $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/api_sid/Messages.json'
                && $request->hasHeader('Authorization', 'Basic ' . base64_encode('api_sid:api_token'))
        );
    }

    public function testApiKeyConnectionsSendRequestsForTheAccount(): void
    {
        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            '*' => $factory->response(['sid' => 'SM123'], 201),
        ]);

        $this->app->make(ConnectionManager::class)->connection('api_key')->message('+15558675310', 'Hello');

        $factory->assertSent(
            static fn(Request $request): bool => $request->url() === 'https://api.dublin.ie1.twilio.com/2010-04-01/Accounts/AC123/Messages.json'
                && $request->hasHeader('Authorization', 'Basic ' . base64_encode('SK123:api_key_secret'))
        );
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
            fn(Container $container): TwilioClientContract => $client
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
            fn(Container $container): TwilioClientContract => $client
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
            fn(Container $container): TwilioClientContract => $client
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
            'twilio.connections.api_key',
            [
                'sid' => 'SK123',
                'token' => 'api_key_secret',
                'from' => '+15558675309',
                'account_sid' => 'AC123',
                'region' => 'ie1',
                'edge' => 'dublin',
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
