# Testing

<div class="docs-note docs-note--new-feature">The testing utilities were added in the 3.1 release.</div>

When testing code that sends messages or places calls, you can replace the client with a fake using the `TwilioClient` facade's `fake()` method. The fake records the messages and calls instead of sending them to Twilio, and provides assertions to check what was sent. Code using the facade or the `BabDev\Twilio\Contracts\TwilioClient` contract, including notifications sent through the "twilio" channel, uses the fake.

```php
use BabDev\Twilio\Facades\TwilioClient;
use BabDev\Twilio\Testing\SentMessage;

public function test_the_customer_is_notified_when_their_order_ships(): void
{
    $twilio = TwilioClient::fake();

    // Ship the order...

    $twilio->assertMessageSentTo('+15558675309', function (SentMessage $message): bool {
        return str_contains($message->message, 'has shipped');
    });
}
```

The fake records the arguments your code passed to the client. Each message is a `BabDev\Twilio\Testing\SentMessage` with the `connection` it was sent through, the `to` number, the `message` text, and any `params`, and each call is a `BabDev\Twilio\Testing\PlacedCall` with the `connection`, `to` number, and `params`. The connection's default "from" number and Messaging Service are not added to the recorded parameters.

The following assertions are available, each accepting an optional callback to filter the recorded messages or calls:

- `assertMessageSent(?callable $callback = null)`
- `assertMessageSentTo(string $to, ?callable $callback = null)`
- `assertMessageNotSent(?callable $callback = null)`
- `assertMessageSentTimes(int $times, ?callable $callback = null)`
- `assertCallPlaced(?callable $callback = null)`
- `assertCallPlacedTo(string $to, ?callable $callback = null)`
- `assertCallNotPlaced(?callable $callback = null)`
- `assertCallPlacedTimes(int $times, ?callable $callback = null)`
- `assertNothingSent()`

The recorded messages and calls can also be retrieved with the `messages()` and `calls()` methods, which accept the same optional callback.

Requests made directly through the Twilio SDK, such as through the `twilio()` method, are not recorded by the fake. These requests are sent with Laravel's HTTP client, so they can be faked with [`Http::fake()`](https://laravel.com/docs/http-client#testing).
