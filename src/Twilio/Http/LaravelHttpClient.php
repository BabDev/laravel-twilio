<?php

namespace BabDev\Twilio\Twilio\Http;

use Illuminate\Http\Client\Factory;
use Twilio\Exceptions\HttpException;
use Twilio\Http\Client;
use Twilio\Http\Response;

final class LaravelHttpClient implements Client
{
    public function __construct(
        private readonly Factory $httpFactory,
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
    ): Response {
        $request = $this->httpFactory->asForm();

        if ($user && $password) {
            $request->withBasicAuth($user, $password);
        }

        $request->withHeaders($headers);

        $requestOptions = [
            'form_params' => $data,
            'query' => $params,
        ];

        try {
            $response = $request->send(
                $method,
                $url,
                $requestOptions,
            );
        } catch (\Exception $exception) {
            throw new HttpException('Unable to complete the HTTP request', 0, $exception);
        }

        return new Response($response->status(), $response->body(), $response->headers());
    }
}
