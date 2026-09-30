<?php

namespace BabDev\Twilio;

use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;

final readonly class TwilioClient implements TwilioClientContract
{
    /**
     * @param string      $from                The default from number to use.
     * @param string|null $messagingServiceSid The default Messaging Service to send messages with, used instead of the from number.
     */
    public function __construct(
        private Client $twilio,
        private string $from,
        private ?string $messagingServiceSid = null,
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

        // A sender passed by the caller is used as is. When not set, fall back to the default Messaging Service, then the default from number.
        if (!isset($params['from']) && !isset($params['messagingServiceSid'])) {
            if ($this->messagingServiceSid) {
                $params['messagingServiceSid'] = $this->messagingServiceSid;
            } else {
                $params['from'] = $this->from;
            }
        }

        return $this->twilio()->messages->create($to, $params);
    }
}
