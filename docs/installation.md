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
