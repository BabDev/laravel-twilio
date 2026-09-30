# Webhooks

<div class="docs-note docs-note--new-feature">The webhook middleware was added in the 3.1 release.</div>

Twilio signs the webhook requests it sends to your application with an `X-Twilio-Signature` header. The `BabDev\Twilio\Http\Middleware\ValidateTwilioSignature` middleware [validates this signature](https://www.twilio.com/docs/usage/webhooks/webhooks-security), rejecting requests without a valid signature with a 403 response.

```php
use App\Http\Controllers\TwilioSmsController;
use BabDev\Twilio\Http\Middleware\ValidateTwilioSignature;
use Illuminate\Support\Facades\Route;

Route::post('/twilio/sms', TwilioSmsController::class)
    ->middleware(ValidateTwilioSignature::class);
```

Twilio's webhook requests do not include a CSRF token, so routes in your `routes/web.php` file should be excluded from CSRF protection in your application's `bootstrap/app.php` file.

```php
use Illuminate\Foundation\Configuration\Middleware;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'twilio/*',
    ]);
})
```

## Choosing a Connection

Signatures are validated using your default connection's credentials. To use another connection, pass its name to the middleware.

```php
Route::post('/twilio/sms', TwilioSmsController::class)
    ->middleware(ValidateTwilioSignature::using('my_new_connection'));
```

Twilio signs requests using your account's auth token. If a connection authenticates with an API key, set the `webhook_token` key for the connection (or the `TWILIO_API_WEBHOOK_TOKEN` environment variable for the default connection) to your account's auth token, as the API key's secret cannot be used to validate signatures. A `Twilio\Exceptions\ConfigurationException` is thrown if a connection has no token to validate signatures with.

## Proxies and Load Balancers

Signatures are validated against the URL Twilio requested, including its scheme and host. If your application is behind a load balancer or proxy that terminates TLS, [configure your trusted proxies](https://laravel.com/docs/requests#configuring-trusted-proxies) so the request's URL matches the URL Twilio requested.
