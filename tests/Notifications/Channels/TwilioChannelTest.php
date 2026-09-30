<?php

namespace BabDev\Twilio\Tests\Notifications\Channels;

use BabDev\Twilio\Contracts\TwilioClient;
use BabDev\Twilio\Notifications\Channels\TwilioChannel;
use BabDev\Twilio\Notifications\Messages\TwilioMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twilio\Rest\Api\V2010\Account\MessageInstance;

final class TwilioChannelTest extends TestCase
{
    public function testANotificationIsSent(): void
    {
        /** @var MockObject&TwilioClient $twilio */
        $twilio = $this->createMock(TwilioClient::class);
        $twilio->expects($this->once())
            ->method('message')
            ->with('+19418675309', 'This is a test', [])
            ->willReturn($this->createStub(MessageInstance::class));

        $notifiable = new class() {
            use Notifiable;

            public function via(mixed $notifiable): array
            {
                return ['twilio'];
            }

            public function routeNotificationForTwilio(Notification $notification): string
            {
                return '+19418675309';
            }
        };

        $notification = new class() extends Notification {
            public function toTwilio(mixed $notifiable): string
            {
                return 'This is a test';
            }
        };

        $this->assertInstanceOf(MessageInstance::class, (new TwilioChannel($twilio))->send($notifiable, $notification));
    }

    public function testANotificationCanReturnATwilioMessage(): void
    {
        /** @var MockObject&TwilioClient $twilio */
        $twilio = $this->createMock(TwilioClient::class);
        $twilio->expects($this->once())
            ->method('message')
            ->with('+19418675309', 'This is a test', ['from' => '+15558675309', 'mediaUrl' => ['https://example.com/image.png']])
            ->willReturn($this->createStub(MessageInstance::class));

        $notifiable = new class() {
            use Notifiable;

            public function routeNotificationForTwilio(Notification $notification): string
            {
                return '+19418675309';
            }
        };

        $notification = new class() extends Notification {
            public function toTwilio(mixed $notifiable): TwilioMessage
            {
                return (new TwilioMessage('This is a test'))
                    ->from('+15558675309')
                    ->mediaUrl('https://example.com/image.png');
            }
        };

        $this->assertInstanceOf(MessageInstance::class, (new TwilioChannel($twilio))->send($notifiable, $notification));
    }

    public function testAnExceptionIsThrownWhenTheNotificationReturnsAnUnsupportedMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        /** @var MockObject&TwilioClient $twilio */
        $twilio = $this->createMock(TwilioClient::class);
        $twilio->expects($this->never())
            ->method('message');

        $notifiable = new class() {
            use Notifiable;

            public function routeNotificationForTwilio(Notification $notification): string
            {
                return '+19418675309';
            }
        };

        $notification = new class() extends Notification {
            public function toTwilio(mixed $notifiable): array
            {
                return ['body' => 'This is a test'];
            }
        };

        (new TwilioChannel($twilio))->send($notifiable, $notification);
    }

    public function testANotificationIsNotSentWhenTheNotifiableDoesNotProvideARecipient(): void
    {
        /** @var MockObject&TwilioClient $twilio */
        $twilio = $this->createMock(TwilioClient::class);
        $twilio->expects($this->never())
            ->method('message');

        $notifiable = new class() {
            use Notifiable;

            public function via(mixed $notifiable): array
            {
                return ['twilio'];
            }

            public function routeNotificationForTwilio(Notification $notification): void {}
        };

        $notification = new class() extends Notification {
            public function toTwilio(mixed $notifiable): string
            {
                return 'This is a test';
            }
        };

        $this->assertNull((new TwilioChannel($twilio))->send($notifiable, $notification));
    }

    public function testANotificationIsNotSentWhenTheNotificationDoesNotProvideAMessage(): void
    {
        /** @var MockObject&TwilioClient $twilio */
        $twilio = $this->createMock(TwilioClient::class);
        $twilio->expects($this->never())
            ->method('message');

        $notifiable = new class() {
            use Notifiable;

            public function via(mixed $notifiable): array
            {
                return ['twilio'];
            }

            public function routeNotificationForTwilio(Notification $notification): string
            {
                return '+19418675309';
            }
        };

        $notification = new class() extends Notification {
            public function toTwilio(mixed $notifiable): void {}
        };

        $this->assertNull((new TwilioChannel($twilio))->send($notifiable, $notification));
    }
}
