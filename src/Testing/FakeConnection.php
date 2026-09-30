<?php

namespace BabDev\Twilio\Testing;

use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;

/**
 * @internal
 */
final readonly class FakeConnection implements TwilioClientContract
{
    public function __construct(
        private TwilioClientFake $fake,
        private string $name,
    ) {}

    public function twilio(): Client
    {
        return $this->fake->twilio();
    }

    public function call(string $to, array $params = []): CallInstance
    {
        return $this->fake->recordCall(new PlacedCall($this->name, $to, $params));
    }

    public function message(string $to, string $message, array $params = []): MessageInstance
    {
        return $this->fake->recordMessage(new SentMessage($this->name, $to, $message, $params));
    }
}
