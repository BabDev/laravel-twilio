<?php

namespace BabDev\Twilio\Tests\Notifications\Messages;

use BabDev\Twilio\Notifications\Messages\TwilioMessage;
use PHPUnit\Framework\TestCase;

final class TwilioMessageTest extends TestCase
{
    public function testTheContentCanBeSet(): void
    {
        $this->assertSame('', (new TwilioMessage())->getContent());
        $this->assertSame('Hello', (new TwilioMessage('Hello'))->getContent());
        $this->assertSame('Goodbye', (new TwilioMessage('Hello'))->content('Goodbye')->getContent());
    }

    public function testAMessageHasNoParametersByDefault(): void
    {
        $this->assertSame([], (new TwilioMessage('Hello'))->getParameters());
    }

    public function testTheSenderCanBeSet(): void
    {
        $message = (new TwilioMessage('Hello'))
            ->from('+15558675309')
            ->messagingService('MG123');

        $this->assertSame(
            [
                'from' => '+15558675309',
                'messagingServiceSid' => 'MG123',
            ],
            $message->getParameters(),
        );
    }

    public function testMediaUrlsAreAdded(): void
    {
        $message = (new TwilioMessage())
            ->mediaUrl('https://example.com/one.png')
            ->mediaUrl('https://example.com/two.png', 'https://example.com/three.png');

        $this->assertSame(
            [
                'mediaUrl' => [
                    'https://example.com/one.png',
                    'https://example.com/two.png',
                    'https://example.com/three.png',
                ],
            ],
            $message->getParameters(),
        );
    }

    public function testAContentTemplateCanBeUsed(): void
    {
        $message = (new TwilioMessage())->template('HX123', ['1' => 'Taylor', '2' => 'Friday']);

        $this->assertSame(
            [
                'contentSid' => 'HX123',
                'contentVariables' => '{"1":"Taylor","2":"Friday"}',
            ],
            $message->getParameters(),
        );
    }

    public function testChangingToAContentTemplateWithoutVariablesRemovesThePreviousVariables(): void
    {
        $message = (new TwilioMessage())
            ->template('HX123', ['1' => 'Taylor'])
            ->template('HX456');

        $this->assertSame(['contentSid' => 'HX456'], $message->getParameters());
    }

    public function testTheStatusCallbackCanBeSet(): void
    {
        $this->assertSame(
            ['statusCallback' => 'https://example.com/status'],
            (new TwilioMessage('Hello'))->statusCallback('https://example.com/status')->getParameters(),
        );
    }

    public function testAMessageCanBeScheduled(): void
    {
        $message = (new TwilioMessage('Hello'))->sendAt(new \DateTimeImmutable('2026-10-01 09:30:00', new \DateTimeZone('America/Chicago')));

        $this->assertSame(
            [
                'sendAt' => '2026-10-01T14:30:00Z',
                'scheduleType' => 'fixed',
            ],
            $message->getParameters(),
        );
    }

    public function testOtherParametersCanBeSet(): void
    {
        $message = (new TwilioMessage('Hello'))
            ->from('+15558675309')
            ->with([
                'from' => '+16518675309',
                'shortenUrls' => true,
            ]);

        $this->assertSame(
            [
                'from' => '+16518675309',
                'shortenUrls' => true,
            ],
            $message->getParameters(),
        );
    }
}
