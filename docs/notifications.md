# Notifications

Messages can be sent as part of Laravel's [notifications system](https://laravel.com/docs/notifications). A notifiable (such as a User model) should include the "twilio" channel in its `via()` method. When routing the notification, the phone number the message should be sent to should be returned.

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * Get the notification's delivery channels.
     *
     * @return string[]
     */
    public function via(mixed $notifiable): array
    {
        // This application's users can receive notifications by mail and Twilio SMS
        return ['mail', 'twilio'];
    }

    /**
     * Get the notification routing information for the Twilio driver.
     */
    public function routeNotificationForTwilio(Notification $notification): string
    {
        return $this->mobile_number;
    }
}
```

For notifications that support being sent as an SMS, you should define a `toTwilio` method on the notification class. This method will receive a $notifiable entity and should return a string containing the message text, or a `TwilioMessage` as described below.

```php
namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

final class PasswordExpiredNotification extends Notification
{
    /**
     * Get the Twilio / SMS representation of the notification.
     *
     * @param User $notifiable
     */
    public function toTwilio(mixed $notifiable): string
    {
        return sprintf('Hello %s, this is a note that the password for your %s account has expired.', $notifiable->name, config('app.name'));
    }
}
```

## Customizing the Message

<div class="docs-note docs-note--new-feature">The <code>BabDev\Twilio\Notifications\Messages\TwilioMessage</code> class was added in the 3.1 release.</div>

To send more than plain text, the `toTwilio` method can return a `BabDev\Twilio\Notifications\Messages\TwilioMessage` instead of a string. The message's content is sent as the message text, and its other settings are passed as parameters when sending the message.

```php
namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use BabDev\Twilio\Notifications\Messages\TwilioMessage;
use Illuminate\Notifications\Notification;

final class OrderShippedNotification extends Notification
{
    public function __construct(
        private readonly Order $order,
    ) {}

    /**
     * Get the Twilio / SMS representation of the notification.
     *
     * @param User $notifiable
     */
    public function toTwilio(mixed $notifiable): TwilioMessage
    {
        return (new TwilioMessage(sprintf('Hello %s, your order has shipped!', $notifiable->name)))
            ->mediaUrl($this->order->trackingMapUrl())
            ->statusCallback(route('twilio.status'));
    }
}
```

The `TwilioMessage` class supports the following methods:

- `content(string $content)` - Sets the message text, which can also be passed to the constructor; the text can be left empty when sending media or a Content Template
- `from(string $from)` - Sends the message from this number instead of the connection's defaults
- `messagingService(string $messagingServiceSid)` - Sends the message through this Messaging Service instead of the connection's defaults
- `mediaUrl(string ...$urls)` - Adds media to the message, sending it as an MMS
- `template(string $contentSid, array $variables = [])` - Sends the message using a [Content Template](https://www.twilio.com/docs/content), with optional values for the template's variables
- `statusCallback(string $url)` - Sets the URL Twilio sends message status updates to
- `sendAt(DateTimeInterface $sendAt)` - [Schedules the message](https://www.twilio.com/docs/messaging/features/message-scheduling) to be sent at a later time, which requires sending through a Messaging Service
- `with(array $parameters)` - Sets any other parameters supported by the Twilio SDK when creating a message, overriding any parameters already set

Your default connection is used for the notification channel by default. If your application utilizes multiple Twilio API connections, you can set the connection which should be used using the `TWILIO_NOTIFICATION_CHANNEL_CONNECTION` environment variable.
