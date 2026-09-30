<?php

namespace BabDev\Twilio\Tests\Notifications;

use BabDev\Twilio\Notifications\Messages\TwilioMessage;
use BabDev\Twilio\Providers\TwilioProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase;

/**
 * Sends notifications through the SDK to check the request sent to Twilio.
 */
final class TwilioNotificationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set(
            'twilio.connections.twilio',
            [
                'sid' => 'account-sid',
                'token' => 'api_token',
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

    public function testAMessageWithMediaAndNoContentIsSentWithoutABody(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        NotificationFacade::route('twilio', '+15558675310')->notify(new class() extends Notification {
            public function via(mixed $notifiable): array
            {
                return ['twilio'];
            }

            public function toTwilio(mixed $notifiable): TwilioMessage
            {
                return (new TwilioMessage())
                    ->mediaUrl('https://example.com/one.png', 'https://example.com/two.png');
            }
        });

        Http::assertSent(static fn(Request $request): bool => str_contains($request->body(), 'MediaUrl=' . urlencode('https://example.com/one.png') . '&MediaUrl=' . urlencode('https://example.com/two.png'))
            && str_contains($request->body(), 'From=' . urlencode('+15558675309'))
            && !str_contains($request->body(), 'Body='));
    }

    public function testAScheduledTemplateMessageIsSentThroughAMessagingService(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        NotificationFacade::route('twilio', '+15558675310')->notify(new class() extends Notification {
            public function via(mixed $notifiable): array
            {
                return ['twilio'];
            }

            public function toTwilio(mixed $notifiable): TwilioMessage
            {
                return (new TwilioMessage())
                    ->messagingService('MG123')
                    ->template('HX123', ['1' => 'Taylor'])
                    ->sendAt(new \DateTimeImmutable('2026-10-01 09:30:00', new \DateTimeZone('America/Chicago')));
            }
        });

        Http::assertSent(static fn(Request $request): bool => $request->data() === [
            'To' => '+15558675310',
            'ScheduleType' => 'fixed',
            'SendAt' => '2026-10-01T14:30:00Z',
            'ContentVariables' => '{"1":"Taylor"}',
            'MessagingServiceSid' => 'MG123',
            'ContentSid' => 'HX123',
        ]);
    }
}
