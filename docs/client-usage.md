# Using The Client

The `BabDev\Twilio\Contracts\TwilioClient` exposes shortcuts which allows quickly creating a new SMS or a call through the Twilio Helper Library.

## Creating a Call

The `BabDev\Twilio\Contracts\TwilioClient::call()` method uses the Twilio library to open a new call. The phone number being called should be provided, and you may optionally include any additional parameters for the underlying library to process. If a call is started, the `Twilio\Rest\Api\V2010\Account\CallInstance` created by the Twilio library is returned.

```php
function startCall(string $phoneNumber, array $params = [])
{
    // This example uses the `TwilioClient` facade
    return TwilioClient::call($phoneNumber, $params);
}
```

The default implementation of the contract, `BabDev\Twilio\TwilioClient`, uses the default phone number configured for your API connection as the "from" number on the call. You can override this if necessary by setting a 'from' key on the params array.

```php
namespace App\Http\Controllers;

use BabDev\Twilio\Contracts\TwilioClient;
use Illuminate\Http\Request;

class StartTwilioCall
{
    public function __invoke(Request $request, TwilioClient $twilio)
    {
        // Starts a call with a custom "from" number passed through the request,
        // you should validate this number is appropriate before calling the client.
        $from = $request->input('from');

        // Starts a call to this number, you should validate this number is appropriate before calling the client.
        $to = $request->input('to');

        // Set any additional parameters for the API call, see https://www.twilio.com/docs/voice/api/call-resource#create-a-call-resource
        // for all available values.
        $params = [
            'from' => $from, // Overrides the default number for the TwilioClient instance
        ];

        $call = $twilio->call($to, $params);

        // Do something with the call info before sending a response

        return response()->json(['call' => (string) $call]);
    }
}
```

## Sending SMS

The `BabDev\Twilio\Contracts\TwilioClient::message()` method uses the Twilio library to send a SMS message. The phone number to send the message to and the message body should be provided, and you may optionally include any additional parameters for the underlying library to process. If the message is sent, the `Twilio\Rest\Api\V2010\Account\MessageInstance` created by the Twilio library is returned.

```php
function sendMessage(string $phoneNumber, string $message, array $params = [])
{
    // This example uses the `TwilioClient` facade
    return TwilioClient::message($phoneNumber, $message, $params);
}
```

The default implementation of the contract, `BabDev\Twilio\TwilioClient`, uses the default phone number configured for your API connection as the "from" number on the message. You can override this if necessary by setting a 'from' key on the params array.

```php
namespace App\Http\Controllers;

use BabDev\Twilio\Contracts\TwilioClient;
use Illuminate\Http\Request;

class SendSMSNotification
{
    public function __invoke(Request $request, TwilioClient $twilio)
    {
        // Sends the message with a custom "from" number passed through the request,
        // you should validate this number is appropriate before calling the client.
        $from = $request->input('from');

        // Sends the message to this number, you should validate this number is appropriate before calling the client.
        $to = $request->input('to');

        // Sends this message to the recipient.
        $message = $request->input('message');

        // Set any additional parameters for the API call, see https://www.twilio.com/docs/sms/send-messages
        // for all available values.
        $params = [
            'from' => $from, // Overrides the default number for the TwilioClient instance
        ];

        $message = $twilio->message($to, $message, $params);

        // Do something with the message info before sending a response

        return response()->json(['message' => (string) $message]);
    }
}
```

## Accessing the SDK

The `BabDev\Twilio\Contracts\TwilioClient::twilio()` method allows access to the underlying `Twilio\Rest\Client` instance, giving full access to the Twilio REST API.

```php
// This example uses the `TwilioClient` facade
TwilioClient::twilio();
```

## HTTP Client

This package provides an implementation of the `Twilio\Http\Client` using [Laravel's HTTP client](https://laravel.com/docs/http-client) to handle the underlying API requests.

If you need to customize the HTTP client used by default in your application, you can extend the `Twilio\Http\Client` service this package creates to use your own HTTP client.
