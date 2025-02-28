<?php

namespace BabDev\Twilio\Facades;

use BabDev\Twilio\ConnectionManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \BabDev\Twilio\Contracts\TwilioClient connection(string|null $name = null)
 * @method static string getDefaultDriver()
 * @method static \Twilio\Rest\Client twilio()
 * @method static \Twilio\Rest\Api\V2010\Account\CallInstance call(string $to, array $params = [])
 * @method static \Twilio\Rest\Api\V2010\Account\MessageInstance message(string $to, string $message, array $params = [])
 * @method static mixed driver(string|null $driver = null)
 * @method static \BabDev\Twilio\ConnectionManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \BabDev\Twilio\ConnectionManager setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \BabDev\Twilio\ConnectionManager forgetDrivers()
 *
 * @see \BabDev\Twilio\ConnectionManager
 */
final class TwilioClient extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ConnectionManager::class;
    }
}
