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
        ],

        'my_new_connection' => [
            'sid' => 'SID-1',
            'token' => 'TOKEN-1',
            'from' => 'PHONE-1',
        ],
    ],
];
```

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
