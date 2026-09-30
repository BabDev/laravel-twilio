<?php

namespace BabDev\Twilio\Twilio\Http;

use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Client\Factory;
use Twilio\AuthStrategy\AuthStrategy;
use Twilio\Exceptions\HttpException;
use Twilio\Http\Client;
use Twilio\Http\Response;

final readonly class LaravelHttpClient implements Client
{
    public function __construct(
        private Factory $httpFactory,
    ) {}

    /**
     * @throws HttpException if the request cannot be completed
     */
    public function request(
        string $method,
        string $url,
        array $params = [],
        array $data = [],
        array $headers = [],
        string $user = null,
        string $password = null,
        int $timeout = null,
        ?AuthStrategy $authStrategy = null,
    ): Response {
        $request = $this->httpFactory->withHeaders($headers);

        if ($user && $password) {
            $request->withBasicAuth($user, $password);
        } elseif ($authStrategy instanceof AuthStrategy) {
            $request->withHeader('Authorization', $authStrategy->getAuthString());
        }

        // Twilio expects list values as repeated keys (`Key=a&Key=b`), not PHP's `Key[0]=a&Key[1]=b`
        if ($params) {
            $url .= (str_contains($url, '?') ? '&' : '?') . Query::build($params, \PHP_QUERY_RFC1738);
        }

        if ($method === 'POST' || $method === 'PUT') {
            $request->withBody(Query::build($data, \PHP_QUERY_RFC1738), 'application/x-www-form-urlencoded');
        }

        try {
            $response = $request->send($method, $url);
        } catch (\Exception $exception) {
            throw new HttpException('Unable to complete the HTTP request', 0, $exception);
        }

        return new Response($response->status(), $response->body(), $response->headers());
    }
}
