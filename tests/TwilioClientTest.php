<?php

namespace BabDev\Twilio\Tests;

use BabDev\Twilio\Facades\TwilioClient;
use BabDev\Twilio\Providers\TwilioProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;

final class TwilioClientTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        // Setup connections configuration
        $app['config']->set(
            'twilio.connections.twilio',
            [
                'sid' => 'account-sid',
                'token' => 'api_token',
                'from' => '+15558675309',
            ]
        );

        $app['config']->set(
            'twilio.connections.messaging_service',
            [
                'sid' => 'account-sid',
                'token' => 'api_token',
                'from' => '+15558675309',
                'messaging_service_sid' => 'MG123',
            ]
        );

        $app['config']->set(
            'twilio.connections.service_only',
            [
                'sid' => 'account-sid',
                'token' => 'api_token',
                'messaging_service_sid' => 'MG123',
            ]
        );

        $app['config']->set(
            'twilio.connections.no_sender',
            [
                'sid' => 'account-sid',
                'token' => 'api_token',
                'from' => '',
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

    public function testTheSdkInstanceCanBeRetrieved(): void
    {
        $this->assertInstanceOf(Client::class, TwilioClient::twilio());
    }

    public function testACallCanBeCreated(): void
    {
        $to = '+15558675309';

        Http::fake([
            'https://api.twilio.com/2010-04-01/Accounts/account-sid/Calls.json' => Http::response(
                $this->getMessageSentResponseContent(config('twilio.connections.twilio.from'), $to),
                201,
            ),
        ]);

        $this->assertInstanceOf(CallInstance::class, TwilioClient::call($to));
    }

    public function testACallCanBeCreatedWithACustomFromNumber(): void
    {
        $to = '+15558675309';
        $customFrom = '+16518675309';

        Http::fake([
            'https://api.twilio.com/2010-04-01/Accounts/account-sid/Calls.json' => Http::response(
                $this->getMessageSentResponseContent($customFrom, $to),
                201,
            ),
        ]);

        $this->assertInstanceOf(CallInstance::class, TwilioClient::call($to));
    }

    public function testAMessageCanBeSent(): void
    {
        $to = '+15558675309';
        $message = 'Test Message';

        Http::fake([
            'https://api.twilio.com/2010-04-01/Accounts/account-sid/Messages.json' => Http::response(
                $this->getMessageSentResponseContent(config('twilio.connections.twilio.from'), $to),
                201,
            ),
        ]);

        $this->assertInstanceOf(MessageInstance::class, TwilioClient::message($to, $message));
    }

    public function testAMessageCanBeSentWithACustomFromNumber(): void
    {
        $to = '+15558675309';
        $customFrom  = '+16518675309';
        $message = 'Test Message';

        Http::fake([
            'https://api.twilio.com/2010-04-01/Accounts/account-sid/Messages.json' => Http::response(
                $this->getMessageSentResponseContent($customFrom, $to),
                201,
            ),
        ]);

        $this->assertInstanceOf(MessageInstance::class, TwilioClient::message($to, $message, ['from' => $customFrom]));
    }

    public function testAMessageIsSentFromTheDefaultNumberWithoutAMessagingService(): void
    {
        $this->fakeMessageResponse();

        TwilioClient::message('+15558675310', 'Test Message');

        Http::assertSent(static fn(Request $request): bool => $request->data() === [
            'To' => '+15558675310',
            'From' => '+15558675309',
            'Body' => 'Test Message',
        ]);
    }

    public function testAMessageIsSentThroughTheConnectionsMessagingService(): void
    {
        $this->fakeMessageResponse();

        TwilioClient::connection('messaging_service')->message('+15558675310', 'Test Message');

        Http::assertSent(static fn(Request $request): bool => $request->data() === [
            'To' => '+15558675310',
            'MessagingServiceSid' => 'MG123',
            'Body' => 'Test Message',
        ]);
    }

    public function testAMessagingServicePassedByTheCallerIsSentWithoutTheDefaultNumber(): void
    {
        $this->fakeMessageResponse();

        TwilioClient::message('+15558675310', 'Test Message', ['messagingServiceSid' => 'MG456']);

        Http::assertSent(static fn(Request $request): bool => $request->data() === [
            'To' => '+15558675310',
            'MessagingServiceSid' => 'MG456',
            'Body' => 'Test Message',
        ]);
    }

    public function testACustomFromNumberIsSentWithoutTheConnectionsMessagingService(): void
    {
        $this->fakeMessageResponse();

        TwilioClient::connection('messaging_service')->message('+15558675310', 'Test Message', ['from' => '+16518675309']);

        Http::assertSent(static fn(Request $request): bool => $request->data() === [
            'To' => '+15558675310',
            'From' => '+16518675309',
            'Body' => 'Test Message',
        ]);
    }

    public function testAMessagingServiceAndACustomFromNumberCanBeSentTogether(): void
    {
        $this->fakeMessageResponse();

        TwilioClient::message('+15558675310', 'Test Message', ['from' => '+16518675309', 'messagingServiceSid' => 'MG456']);

        Http::assertSent(static fn(Request $request): bool => $request->data() === [
            'To' => '+15558675310',
            'From' => '+16518675309',
            'MessagingServiceSid' => 'MG456',
            'Body' => 'Test Message',
        ]);
    }

    public function testAMessageIsSentThroughAServiceOnlyConnection(): void
    {
        $this->fakeMessageResponse();

        TwilioClient::connection('service_only')->message('+15558675310', 'Test Message');

        Http::assertSent(static fn(Request $request): bool => $request->data() === [
            'To' => '+15558675310',
            'MessagingServiceSid' => 'MG123',
            'Body' => 'Test Message',
        ]);
    }

    public function testACallCanBeCreatedFromAServiceOnlyConnectionWithACustomFromNumber(): void
    {
        Http::fake([
            'https://api.twilio.com/2010-04-01/Accounts/account-sid/Calls.json' => Http::response(
                $this->getMessageSentResponseContent('+16518675309', '+15558675310'),
                201,
            ),
        ]);

        TwilioClient::connection('service_only')->call('+15558675310', ['from' => '+16518675309', 'url' => 'https://example.com/twiml']);

        Http::assertSent(static fn(Request $request): bool => $request->data()['From'] === '+16518675309');
    }

    public function testACallCannotBeCreatedWithoutAFromNumber(): void
    {
        Http::fake();

        try {
            TwilioClient::connection('service_only')->call('+15558675310', ['url' => 'https://example.com/twiml']);

            $this->fail('A call without a from number should not be created.');
        } catch (ConfigurationException $exception) {
            $this->assertSame('A "from" number is required to create a call.', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function testAMessageCannotBeSentWithoutASender(): void
    {
        Http::fake();

        try {
            TwilioClient::connection('no_sender')->message('+15558675310', 'Test Message');

            $this->fail('A message without a sender should not be sent.');
        } catch (ConfigurationException $exception) {
            $this->assertSame('A "from" number or Messaging Service SID is required to send a message.', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    private function fakeMessageResponse(): void
    {
        Http::fake([
            'https://api.twilio.com/2010-04-01/Accounts/account-sid/Messages.json' => Http::response(
                $this->getMessageSentResponseContent('+15558675309', '+15558675310'),
                201,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getMessageSentResponseContent(string $from, string $to): array
    {
        $date = Date::now()->toRfc822String();

        return [
            'body'                  => 'Test',
            'num_segments'          => '1',
            'direction'             => 'outbound-api',
            'from'                  => $from,
            'date_updated'          => $date,
            'price'                 => null,
            'error_message'         => null,
            'uri'                   => '/2010-04-01/Accounts/account-sid/Messages/message-sid.json',
            'account_sid'           => 'account-sid',
            'num_media'             => '0',
            'to'                    => $to,
            'date_created'          => $date,
            'status'                => 'queued',
            'sid'                   => 'message-sid',
            'date_sent'             => null,
            'messaging_service_sid' => null,
            'error_code'            => null,
            'price_unit'            => 'USD',
            'api_version'           => '2010-04-01',
            'subresource_uris'      => [
                'media' => '/2010-04-01/Accounts/account-sid/Messages/message-sid/Media.json',
            ],
        ];
    }
}
