<?php

namespace BabDev\Twilio\Notifications\Channels;

use BabDev\Twilio\Contracts\TwilioClient;
use Illuminate\Notifications\Notification;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\MessageInstance;

final class TwilioChannel
{
    public function __construct(
        private readonly TwilioClient $twilio,
    ) {}

    /**
     * @throws TwilioException on Twilio API failure
     */
    public function send(mixed $notifiable, Notification $notification): ?MessageInstance
    {
        $to = $notifiable->routeNotificationFor('twilio', $notification);

        if (!$to) {
            return null;
        }

        $message = $notification->toTwilio($notifiable);

        if (!$message) {
            return null;
        }

        return $this->twilio->message($to, $message);
    }
}
