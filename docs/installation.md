# Installation & Setup

To install this package, run the following [Composer](https://getcomposer.org/) command:

```bash
composer require babdev/laravel-twilio
```

## Register The Package

If your application is not using package discovery, you will need to add the service provider to your `config/app.php` file.

```php
return [
    'providers' => [
        BabDev\Twilio\Providers\TwilioProvider::class,
    ],
];
```

To use the facade, you will also need to register it in your `config/app.php` file.

```php
return [
    'aliases' => [
        'TwilioClient' => BabDev\Twilio\Facades\TwilioClient::class,
    ],
];
```

## Publish Resources

If you need to customize the package configuration, you can publish it to your application's `config` directory with the following command:

```bash
php artisan vendor:publish --provider="BabDev\Twilio\Providers\TwilioProvider" --tag="config"
```

## Setup

### Setting Environment Variables

The below environment variables should be set in your application's `.env` file:

- `TWILIO_CONNECTION` - The name of the default Twilio API connection for your application; if using a single connection this does not need to be changed
- `TWILIO_NOTIFICATION_CHANNEL_CONNECTION` - If using Laravel's notifications system, the name of a Twilio API connection to use in the notification channel (defaulting to your default connection); if using a single connection this does not need to be changed
- `TWILIO_API_SID` - The Twilio API SID to use for the default Twilio API connection
- `TWILIO_API_AUTH_TOKEN` - The Twilio API authentication token to use for the default Twilio API connection
- `TWILIO_API_FROM_NUMBER` - The default sending phone number to use for the default Twilio API connection, note the sending phone number can be changed on a per-message basis
- `TWILIO_API_ACCOUNT_SID` - (Optional) The SID of the Twilio account to use for the default Twilio API connection; this is required when `TWILIO_API_SID` and `TWILIO_API_AUTH_TOKEN` are an API key's SID and secret
- `TWILIO_API_REGION` - (Optional) The Twilio region to send requests for the default Twilio API connection to
- `TWILIO_API_EDGE` - (Optional) The Twilio edge location to send requests for the default Twilio API connection through
- `TWILIO_API_MESSAGING_SERVICE_SID` - (Optional) The SID of a Messaging Service to send messages for the default Twilio API connection through; when set, Twilio chooses the sender from the service's sender pool, and `TWILIO_API_FROM_NUMBER` is still used for calls
