# Connection Management

The default `BabDev\Twilio\Contracts\TwilioClient` implementation is the `BabDev\Twilio\ConnectionManager` class, which is an extension of the `Illuminate\Support\Manager` class and allows creating and retrieving named connections to use when interacting with the Twilio Helper Library.

## Multiple Connections

If your application uses multiple sets of REST API credentials for the Twilio API, you can add the data for multiple connections to the package's configuration. If you have not already, you will need to publish this package's configuration.

Then, in your newly created `config/twilio.php` file, you can add new connections to the `connections` array.

<div class="docs-note">The default "twilio" connection has been created for you and uses environment variables by default. You are free to change the default connection for your application and the name of the "twilio" connection if desired.</div>

```php
<?php

return [
    'default' => env('TWILIO_CONNECTION', 'twilio'),

    'notification_channel' => env('TWILIO_NOTIFICATION_CHANNEL_CONNECTION', env('TWILIO_CONNECTION', 'twilio')),

    'connections' => [
        'twilio' => [
            'sid' => env('TWILIO_API_SID', ''),
            'token' => env('TWILIO_API_AUTH_TOKEN', ''),
            'from' => env('TWILIO_API_FROM_NUMBER', ''),
            'account_sid' => env('TWILIO_API_ACCOUNT_SID'),
            'region' => env('TWILIO_API_REGION'),
            'edge' => env('TWILIO_API_EDGE'),
            'messaging_service_sid' => env('TWILIO_API_MESSAGING_SERVICE_SID'),
        ],

        'my_new_connection' => [
            'sid' => 'SID-1',
            'token' => 'TOKEN-1',
            'from' => 'PHONE-1',
        ],

        'my_api_key_connection' => [
            'sid' => 'API-KEY-SID',
            'token' => 'API-KEY-SECRET',
            'from' => 'PHONE-2',
            'account_sid' => 'ACCOUNT-SID',
            'region' => 'ie1',
            'edge' => 'dublin',
        ],
    ],
];
```

When authenticating with an [API key](https://www.twilio.com/docs/iam/api-keys), set the `sid` and `token` to the API key's SID and secret, and set the `account_sid` to the SID of the account the key belongs to. The optional `region` and `edge` keys send a connection's requests through a specific [Twilio region and edge location](https://www.twilio.com/docs/global-infrastructure/edge-locations).

To send a connection's messages through a [Messaging Service](https://www.twilio.com/docs/messaging/services), set the optional `messaging_service_sid` key. Twilio then chooses the sender for each message from the service's sender pool, while the `from` number is still used for calls.

## Customizing Client Creation

You can customize the creation of `BabDev\Twilio\Contracts\TwilioClient` instances using the `BabDev\Twilio\ConnectionManager::extend()` method, this allows you to define a custom callback to be used for creating a client. You may either override the creation of a named connection from your configuration, or dynamically create a new connection.

```php
<?php

namespace App\Providers;

use App\Twilio\TwilioClient;
use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Twilio\Http\Client as HttpClient;
use Twilio\Rest\Client as RestClient;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        \TwilioClient::extend(
            'custom',
            function (Container $container): TwilioClientContract {
                /*
                 * Create a custom client from your application.
                 *
                 * For your convenience, you can use the Laravel container to create the
                 * Twilio\Rest\Client SDK class and its internal Twilio\Http\Client dependency
                 */
                return $container->make(
                    TwilioClient::class,
                    [
                        'twilio' => $container->make(
                            RestClient::class,
                            [
                                'username' => 'my_username',
                                'password' => 'my_password',
                                'httpClient' => $container->make(HttpClient::class),
                            ]
                        ),
                        'from' => 'my_sender_number',
                    ]
                );
            }
        );
    }
}
```
