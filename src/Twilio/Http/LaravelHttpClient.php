<?php

namespace BabDev\Twilio\Twilio\Http;

use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Twilio\AuthStrategy\AuthStrategy;
use Twilio\Exceptions\HttpException;
use Twilio\Http\Client;
use Twilio\Http\File;
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
        ?string $user = null,
        ?string $password = null,
        ?int $timeout = null,
        ?AuthStrategy $authStrategy = null,
    ): Response {
        $request = $this->httpFactory
            ->withoutRedirecting()
            ->withHeaders($headers);

        if ($user && $password) {
            $request->withBasicAuth($user, $password);
        } elseif ($authStrategy instanceof AuthStrategy) {
            $request->withHeader('Authorization', $authStrategy->getAuthString());
        }

        if ($timeout !== null) {
            $request->timeout($timeout);
        }

        // Twilio expects list values as repeated keys (`Key=a&Key=b`), not PHP's `Key[0]=a&Key[1]=b`
        if ($params) {
            $url .= (str_contains($url, '?') ? '&' : '?') . Query::build($params, \PHP_QUERY_RFC1738);
        }

        $method = strtoupper(trim($method));
        $body = [];

        if (\in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if ($this->hasFile($data)) {
                $this->attachMultipartData($request, $data);
            } elseif (($headers['Content-Type'] ?? null) === 'application/json') {
                $request->asJson();
                $body = $data;
            } else {
                $request->withBody(Query::build($data, \PHP_QUERY_RFC1738), 'application/x-www-form-urlencoded');
            }
        }

        try {
            $response = match ($method) {
                'POST' => $request->post($url, $body),
                'PUT' => $request->put($url, $body),
                'PATCH' => $request->patch($url, $body),
                default => $request->send($method, $url),
            };
        } catch (\Exception $exception) {
            throw new HttpException('Unable to complete the HTTP request', 0, $exception);
        }

        return new Response($response->status(), $response->body(), $response->headers());
    }

    private function hasFile(array $data): bool
    {
        foreach ($data as $value) {
            if ($value instanceof File) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws HttpException             if a file cannot be opened
     * @throws \InvalidArgumentException if a file's contents are not supported
     */
    private function attachMultipartData(PendingRequest $request, array $data): void
    {
        foreach ($data as $name => $value) {
            if ($value instanceof File) {
                $request->attach(
                    (string) $name,
                    $this->getFileContents($value),
                    $value->getFileName(),
                    $value->getContentType() !== null ? ['Content-Type' => $value->getContentType()] : [],
                );

                continue;
            }

            // List values become one part per item, the multipart equivalent of repeated query keys
            foreach (\is_array($value) ? $value : [$value] as $item) {
                if ($item === null) {
                    continue;
                }

                $request->attach((string) $name, (string) $item);
            }
        }
    }

    /**
     * Resolves a file's contents using the same rules as the SDK's {@see \Twilio\Http\CurlClient}.
     *
     * @return resource|string
     *
     * @throws HttpException             if the file cannot be opened
     * @throws \InvalidArgumentException if the file's contents are not supported
     */
    private function getFileContents(File $file): mixed
    {
        $contents = $file->getContents();

        // A file without contents is read from its path
        if ($contents === null) {
            $contents = @fopen($file->getFileName(), 'rb');

            if ($contents === false) {
                throw new HttpException(\sprintf('Unable to open file "%s" for upload', $file->getFileName()));
            }

            return $contents;
        }

        if (\is_resource($contents) || \is_string($contents)) {
            return $contents;
        }

        throw new \InvalidArgumentException('Unsupported content type');
    }
}
