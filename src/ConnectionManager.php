<?php

namespace BabDev\Twilio;

use BabDev\Twilio\Contracts\TwilioClient as TwilioClientContract;
use Illuminate\Support\Manager;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Http\Client as HttpClient;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client as RestClient;

class ConnectionManager extends Manager implements TwilioClientContract
{
    /**
     * @throws \InvalidArgumentException if the driver cannot be created
     */
    public function connection(?string $name = null): TwilioClientContract
    {
        return $this->driver($name);
    }

    /**
     * Get the default channel driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('twilio.default', 'twilio');
    }

    /**
     * @param string $driver
     *
     * @throws \InvalidArgumentException if the driver cannot be created
     */
    protected function createDriver($driver): TwilioClientContract
    {
        try {
            return parent::createDriver($driver);
        } catch (\InvalidArgumentException $e) {
            if (!$this->config->has("twilio.connections.$driver")) {
                throw $e;
            }

            try {
                /** @var RestClient $twilio */
                $twilio = $this->container->make(
                    RestClient::class,
                    [
                        'username' => $this->config->get("twilio.connections.$driver.sid"),
                        'password' => $this->config->get("twilio.connections.$driver.token"),
                        'accountSid' => $this->config->get("twilio.connections.$driver.account_sid"),
                        'region' => $this->config->get("twilio.connections.$driver.region"),
                        'httpClient' => $this->container->make(HttpClient::class),
                    ]
                );

                if ($edge = $this->config->get("twilio.connections.$driver.edge")) {
                    $twilio->setEdge($edge);
                }

                return $this->container->make(
                    TwilioClient::class,
                    [
                        'twilio' => $twilio,
                        'from' => $this->config->get("twilio.connections.$driver.from"),
                        'messagingServiceSid' => $this->config->get("twilio.connections.$driver.messaging_service_sid"),
                    ]
                );
            } catch (ConfigurationException $e) {
                throw new \InvalidArgumentException("Driver [$driver] is not correctly configured.", $e->getCode(), $e);
            }
        }
    }

    public function twilio(): RestClient
    {
        return $this->connection()->twilio();
    }

    /**
     * @throws TwilioException on Twilio API failure
     */
    public function call(string $to, array $params = []): CallInstance
    {
        return $this->connection()->call($to, $params);
    }

    /**
     * @throws TwilioException on Twilio API failure
     */
    public function message(string $to, string $message, array $params = []): MessageInstance
    {
        return $this->connection()->message($to, $message, $params);
    }
}
