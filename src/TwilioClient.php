<?php

namespace BabDev\Twilio;

use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;

final class TwilioClient implements TwilioClientContract
{
    /**
     * @param string $from The default from number to use.
     */
    public function __construct(
        private readonly Client $twilio,
        private readonly string $from,
    ) {}

    public function twilio(): Client
    {
        return $this->twilio;
    }

    /**
     * @throws TwilioException on Twilio API failure
     */
    public function call(string $to, array $params = []): CallInstance
    {
        // Allows specifying a custom from number with fallback
        $from = $params['from'] ?? $this->from;
        unset($params['from']);

        return $this->twilio()->calls->create($to, $from, $params);
    }

    /**
     * @throws TwilioException on Twilio API failure
     */
    public function message(string $to, string $message, array $params = []): MessageInstance
    {
        $params['body'] = $message;

        // Allows specifying a custom from number with fallback
        $params['from'] ??= $this->from;

        return $this->twilio()->messages->create($to, $params);
    }
}
