<?php

namespace BabDev\Twilio;

use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;

final readonly class TwilioClient implements TwilioClientContract
{
    /**
     * @param string|null $from                The default from number to use, optional when only sending messages with a Messaging Service.
     * @param string|null $messagingServiceSid The default Messaging Service to send messages with, used instead of the from number.
     */
    public function __construct(
        private Client $twilio,
        private ?string $from = null,
        private ?string $messagingServiceSid = null,
    ) {}

    public function twilio(): Client
    {
        return $this->twilio;
    }

    /**
     * @throws ConfigurationException if there is no from number to call with
     * @throws TwilioException        on Twilio API failure
     */
    public function call(string $to, array $params = []): CallInstance
    {
        // Allows specifying a custom from number with fallback
        $from = $params['from'] ?? $this->from;
        unset($params['from']);

        if (!$from) {
            throw new ConfigurationException('A "from" number is required to create a call.');
        }

        return $this->twilio()->calls->create($to, $from, $params);
    }

    /**
     * @throws ConfigurationException if there is no from number or Messaging Service to send with
     * @throws TwilioException        on Twilio API failure
     */
    public function message(string $to, string $message, array $params = []): MessageInstance
    {
        // Twilio requires a body only when the message has no media or Content Template
        if ($message !== '') {
            $params['body'] = $message;
        }

        // A sender passed by the caller is used as is. When not set, fall back to the default Messaging Service, then the default from number.
        if (!isset($params['from']) && !isset($params['messagingServiceSid'])) {
            if ($this->messagingServiceSid) {
                $params['messagingServiceSid'] = $this->messagingServiceSid;
            } elseif ($this->from) {
                $params['from'] = $this->from;
            } else {
                throw new ConfigurationException('A "from" number or Messaging Service SID is required to send a message.');
            }
        }

        return $this->twilio()->messages->create($to, $params);
    }
}
