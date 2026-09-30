<?php

namespace BabDev\Twilio\Tests\Testing;

use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use BabDev\Twilio\Facades\TwilioClient;
use BabDev\Twilio\Notifications\Messages\TwilioMessage;
use BabDev\Twilio\Providers\TwilioProvider;
use BabDev\Twilio\Testing\PlacedCall;
use BabDev\Twilio\Testing\SentMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\ExpectationFailedException;

final class TwilioClientFakeTest extends TestCase
{
    public function testTheFakeReplacesTheClient(): void
    {
        $fake = TwilioClient::fake();

        $this->assertSame($fake, $this->app->make(TwilioClientContract::class));

        $this->app->make(TwilioClientContract::class)->message('+15558675310', 'Hello');

        $fake->assertMessageSent();
    }

    public function testMessagesAreRecordedWithoutSendingRequests(): void
    {
        Http::fake();

        $fake = TwilioClient::fake();

        $message = TwilioClient::message('+15558675310', 'Hello', ['from' => '+15558675309']);

        $this->assertStringStartsWith('SM', $message->sid);
        $this->assertSame('+15558675310', $message->to);
        $this->assertSame('+15558675309', $message->from);
        $this->assertSame('Hello', $message->body);
        $this->assertEquals([new SentMessage('twilio', '+15558675310', 'Hello', ['from' => '+15558675309'])], $fake->messages());

        Http::assertNothingSent();
    }

    public function testMessageAssertions(): void
    {
        $fake = TwilioClient::fake();

        TwilioClient::message('+15558675310', 'Hello');
        TwilioClient::message('+15558675310', 'Goodbye', ['messagingServiceSid' => 'MG123']);

        $fake->assertMessageSent(static fn(SentMessage $message): bool => $message->message === 'Hello');
        $fake->assertMessageSentTo('+15558675310', static fn(SentMessage $message): bool => ($message->params['messagingServiceSid'] ?? null) === 'MG123');
        $fake->assertMessageNotSent(static fn(SentMessage $message): bool => $message->to === '+15558675311');
        $fake->assertMessageSentTimes(2);
        $fake->assertMessageSentTimes(1, static fn(SentMessage $message): bool => $message->message === 'Goodbye');
        $fake->assertCallNotPlaced();
    }

    public function testAssertingAMessageWasSentFailsWhenItWasNot(): void
    {
        $fake = TwilioClient::fake();

        TwilioClient::message('+15558675310', 'Hello');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected message was not sent to [+15558675311].');

        $fake->assertMessageSentTo('+15558675311');
    }

    public function testAssertingAMessageWasSentAnExactNumberOfTimesFailsWhenItWasNot(): void
    {
        $fake = TwilioClient::fake();

        TwilioClient::message('+15558675310', 'Hello');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected message was sent 1 times instead of 2 times.');

        $fake->assertMessageSentTimes(2);
    }

    public function testCallsAreRecorded(): void
    {
        $fake = TwilioClient::fake();

        $call = TwilioClient::call('+15558675310', ['url' => 'https://example.com/twiml']);

        $this->assertStringStartsWith('CA', $call->sid);
        $this->assertSame('+15558675310', $call->to);

        $fake->assertCallPlaced();
        $fake->assertCallPlacedTo('+15558675310', static fn(PlacedCall $call): bool => $call->params['url'] === 'https://example.com/twiml');
        $fake->assertCallNotPlaced(static fn(PlacedCall $call): bool => $call->to === '+15558675311');
        $fake->assertCallPlacedTimes(1);
        $fake->assertMessageNotSent();
    }

    public function testAssertingACallWasPlacedFailsWhenItWasNot(): void
    {
        $fake = TwilioClient::fake();

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected call was not placed.');

        $fake->assertCallPlaced();
    }

    public function testMessagesAndCallsAreRecordedWithTheirConnection(): void
    {
        $fake = TwilioClient::fake();

        TwilioClient::message('+15558675310', 'Hello');
        TwilioClient::connection('other')->message('+15558675310', 'Hello');
        TwilioClient::connection('other')->call('+15558675310');

        $fake->assertMessageSentTimes(1, static fn(SentMessage $message): bool => $message->connection === 'twilio');
        $fake->assertMessageSentTimes(1, static fn(SentMessage $message): bool => $message->connection === 'other');
        $fake->assertCallPlaced(static fn(PlacedCall $call): bool => $call->connection === 'other');
    }

    public function testAssertingNothingWasSentListsWhatWasSent(): void
    {
        $fake = TwilioClient::fake();

        $fake->assertNothingSent();

        TwilioClient::message('+15558675310', 'Hello');
        TwilioClient::call('+15558675311');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("The following were sent unexpectedly:\n\n- Message to [+15558675310]\n- Call to [+15558675311]");

        $fake->assertNothingSent();
    }

    public function testNotificationsAreRecorded(): void
    {
        $fake = TwilioClient::fake();

        NotificationFacade::route('twilio', '+15558675310')->notify(new FakeTestNotification());

        $fake->assertMessageSentTo('+15558675310', static fn(SentMessage $message): bool => $message->message === 'Your order has shipped'
            && $message->params === ['mediaUrl' => ['https://example.com/map.png']]);
    }

    public function testNotificationsAreRecordedWhenTheChannelWasCreatedBeforeFaking(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        // Sending a notification creates the channel with the real client
        NotificationFacade::route('twilio', '+15558675310')->notify(new FakeTestNotification());

        $fake = TwilioClient::fake();

        NotificationFacade::route('twilio', '+15558675310')->notify(new FakeTestNotification());

        $fake->assertMessageSentTimes(1);
        Http::assertSentCount(1);
    }

    public function testRequestsMadeDirectlyThroughTheSdkCanBeFakedWithTheHttpClient(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $fake = TwilioClient::fake();

        $fake->twilio()->messages->create('+15558675310', ['from' => '+15558675309', 'body' => 'Hello']);

        Http::assertSent(static fn(Request $request): bool => str_starts_with($request->url(), 'https://api.twilio.com/2010-04-01/Accounts/'));
        $fake->assertNothingSent();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('twilio.connections.twilio', ['sid' => 'AC123', 'token' => 'api_token', 'from' => '+15558675309']);
        $app['config']->set('twilio.connections.other', ['sid' => 'AC456', 'token' => 'other_token', 'from' => '+15558675308']);
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

final class FakeTestNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['twilio'];
    }

    public function toTwilio(mixed $notifiable): TwilioMessage
    {
        return (new TwilioMessage('Your order has shipped'))->mediaUrl('https://example.com/map.png');
    }
}
