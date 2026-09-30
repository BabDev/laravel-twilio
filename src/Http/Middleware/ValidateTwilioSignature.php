<?php

namespace BabDev\Twilio\Http\Middleware;

use GuzzleHttp\Psr7\Query;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Security\RequestValidator;

/**
 * Validates the signature of a webhook request from Twilio.
 */
final readonly class ValidateTwilioSignature
{
    public function __construct(
        private Repository $config,
    ) {}

    /**
     * Get the middleware to validate requests using the given connection's credentials.
     */
    public static function using(string $connection): string
    {
        return self::class . ':' . $connection;
    }

    /**
     * @param \Closure(Request): Response $next
     *
     * @throws AccessDeniedHttpException if the request does not have a valid signature
     * @throws ConfigurationException    if the connection does not have a token to validate with
     */
    public function handle(Request $request, \Closure $next, ?string $connection = null): Response
    {
        $signature = $request->header('X-Twilio-Signature');

        if (!\is_string($signature) || $signature === '' || !$this->validator($connection)->validate($signature, $this->url($request), $this->data($request))) {
            throw new AccessDeniedHttpException('Invalid Twilio signature.');
        }

        return $next($request);
    }

    /**
     * @throws ConfigurationException if the connection does not have a token to validate with
     */
    private function validator(?string $connection): RequestValidator
    {
        $connection ??= $this->config->get('twilio.default', 'twilio');

        // Twilio signs requests with the account's auth token, so connections using an API key need the auth token separately
        $token = $this->config->get("twilio.connections.$connection.webhook_token") ?: $this->config->get("twilio.connections.$connection.token");

        if (!\is_string($token) || $token === '') {
            throw new ConfigurationException(\sprintf('The Twilio connection "%s" does not have a token to validate webhook signatures with.', $connection));
        }

        return new RequestValidator($token);
    }

    /**
     * Twilio signs the exact URL it requested, so the raw request URI is used as the full URL normalizes the query string.
     */
    private function url(Request $request): string
    {
        return $request->getSchemeAndHttpHost() . $request->getRequestUri();
    }

    /**
     * The raw body is used as middleware such as TrimStrings may have already changed the request's input.
     *
     * @return array<string, string|list<string>>|string
     */
    private function data(Request $request): array|string
    {
        // JSON requests are signed with a hash of the body in the URL instead of the request's parameters
        if ($request->query->has('bodySHA256')) {
            return $request->getContent();
        }

        return Query::parse($request->getContent());
    }
}
