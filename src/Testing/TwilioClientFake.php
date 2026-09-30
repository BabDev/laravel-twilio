<?php

namespace BabDev\Twilio\Testing;

use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use Illuminate\Support\Testing\Fakes\Fake;
use PHPUnit\Framework\Assert as PHPUnit;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;

/**
 * Records the messages sent and calls placed through the client instead of sending them to Twilio.
 */
final class TwilioClientFake implements TwilioClientContract, Fake
{
    /**
     * @var list<SentMessage>
     */
    private array $messages = [];

    /**
     * @var list<PlacedCall>
     */
    private array $calls = [];

    /**
     * @var array<string, FakeConnection>
     */
    private array $connections = [];

    /**
     * @param Client $twilio The SDK client for requests made directly through the SDK, which are not recorded.
     */
    public function __construct(
        private readonly Client $twilio,
        private readonly string $defaultConnection = 'twilio',
    ) {}

    public function connection(?string $name = null): TwilioClientContract
    {
        $name ??= $this->defaultConnection;

        return $this->connections[$name] ??= new FakeConnection($this, $name);
    }

    public function twilio(): Client
    {
        return $this->twilio;
    }

    public function call(string $to, array $params = []): CallInstance
    {
        return $this->connection()->call($to, $params);
    }

    public function message(string $to, string $message, array $params = []): MessageInstance
    {
        return $this->connection()->message($to, $message, $params);
    }

    /**
     * @param (callable(SentMessage): bool)|null $callback
     */
    public function assertMessageSent(?callable $callback = null): void
    {
        PHPUnit::assertNotEmpty($this->messages($callback), 'The expected message was not sent.');
    }

    /**
     * @param (callable(SentMessage): bool)|null $callback
     */
    public function assertMessageSentTo(string $to, ?callable $callback = null): void
    {
        PHPUnit::assertNotEmpty(
            $this->messages(static fn(SentMessage $message): bool => $message->to === $to && ($callback === null || $callback($message))),
            \sprintf('The expected message was not sent to [%s].', $to),
        );
    }

    /**
     * @param (callable(SentMessage): bool)|null $callback
     */
    public function assertMessageNotSent(?callable $callback = null): void
    {
        PHPUnit::assertEmpty($this->messages($callback), 'An unexpected message was sent.');
    }

    /**
     * @param (callable(SentMessage): bool)|null $callback
     */
    public function assertMessageSentTimes(int $times, ?callable $callback = null): void
    {
        $count = \count($this->messages($callback));

        PHPUnit::assertSame($times, $count, \sprintf('The expected message was sent %d times instead of %d times.', $count, $times));
    }

    /**
     * @param (callable(PlacedCall): bool)|null $callback
     */
    public function assertCallPlaced(?callable $callback = null): void
    {
        PHPUnit::assertNotEmpty($this->calls($callback), 'The expected call was not placed.');
    }

    /**
     * @param (callable(PlacedCall): bool)|null $callback
     */
    public function assertCallPlacedTo(string $to, ?callable $callback = null): void
    {
        PHPUnit::assertNotEmpty(
            $this->calls(static fn(PlacedCall $call): bool => $call->to === $to && ($callback === null || $callback($call))),
            \sprintf('The expected call was not placed to [%s].', $to),
        );
    }

    /**
     * @param (callable(PlacedCall): bool)|null $callback
     */
    public function assertCallNotPlaced(?callable $callback = null): void
    {
        PHPUnit::assertEmpty($this->calls($callback), 'An unexpected call was placed.');
    }

    /**
     * @param (callable(PlacedCall): bool)|null $callback
     */
    public function assertCallPlacedTimes(int $times, ?callable $callback = null): void
    {
        $count = \count($this->calls($callback));

        PHPUnit::assertSame($times, $count, \sprintf('The expected call was placed %d times instead of %d times.', $count, $times));
    }

    public function assertNothingSent(): void
    {
        $sent = [
            ...array_map(static fn(SentMessage $message): string => \sprintf('Message to [%s]', $message->to), $this->messages),
            ...array_map(static fn(PlacedCall $call): string => \sprintf('Call to [%s]', $call->to), $this->calls),
        ];

        PHPUnit::assertEmpty($sent, "The following were sent unexpectedly:\n\n- " . implode("\n- ", $sent) . "\n");
    }

    /**
     * @param (callable(SentMessage): bool)|null $callback
     *
     * @return list<SentMessage>
     */
    public function messages(?callable $callback = null): array
    {
        return $callback === null ? $this->messages : array_values(array_filter($this->messages, $callback));
    }

    /**
     * @param (callable(PlacedCall): bool)|null $callback
     *
     * @return list<PlacedCall>
     */
    public function calls(?callable $callback = null): array
    {
        return $callback === null ? $this->calls : array_values(array_filter($this->calls, $callback));
    }

    /**
     * @internal
     */
    public function recordMessage(SentMessage $message): MessageInstance
    {
        $this->messages[] = $message;

        return new MessageInstance(
            $this->twilio->api->v2010,
            [
                'sid' => 'SM' . bin2hex(random_bytes(16)),
                'account_sid' => $this->twilio->getAccountSid(),
                'to' => $message->to,
                'from' => $message->params['from'] ?? null,
                'messaging_service_sid' => $message->params['messagingServiceSid'] ?? null,
                'body' => $message->message,
                'num_media' => (string) \count($message->params['mediaUrl'] ?? []),
                'status' => 'queued',
                'direction' => 'outbound-api',
            ],
            $this->twilio->getAccountSid(),
        );
    }

    /**
     * @internal
     */
    public function recordCall(PlacedCall $call): CallInstance
    {
        $this->calls[] = $call;

        return new CallInstance(
            $this->twilio->api->v2010,
            [
                'sid' => 'CA' . bin2hex(random_bytes(16)),
                'account_sid' => $this->twilio->getAccountSid(),
                'to' => $call->to,
                'from' => $call->params['from'] ?? null,
                'status' => 'queued',
                'direction' => 'outbound-api',
            ],
            $this->twilio->getAccountSid(),
        );
    }
}
