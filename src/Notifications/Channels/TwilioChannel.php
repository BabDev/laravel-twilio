<?php

namespace BabDev\Twilio\Notifications\Channels;

use BabDev\Twilio\Contracts\TwilioClient;
use BabDev\Twilio\Notifications\Messages\TwilioMessage;
use Illuminate\Notifications\Notification;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\MessageInstance;

final readonly class TwilioChannel
{
    public function __construct(
        private TwilioClient $twilio,
    ) {}

    /**
     * @throws \InvalidArgumentException if the notification does not return a string or TwilioMessage
     * @throws TwilioException           on Twilio API failure
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

        if (\is_string($message)) {
            $message = new TwilioMessage($message);
        }

        if (!$message instanceof TwilioMessage) {
            throw new \InvalidArgumentException(\sprintf('The %s::toTwilio() method must return a string or an instance of %s, received "%s".', $notification::class, TwilioMessage::class, get_debug_type($message)));
        }

        return $this->twilio->message($to, $message->getContent(), $message->getParameters());
    }
}
