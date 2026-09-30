<?php

namespace BabDev\Twilio\Tests\Http\Middleware;

use BabDev\Twilio\Http\Middleware\ValidateTwilioSignature;
use BabDev\Twilio\Providers\TwilioProvider;
use GuzzleHttp\Psr7\Query;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Security\RequestValidator;

final class ValidateTwilioSignatureTest extends TestCase
{
    public function testAValidFormRequestIsAccepted(): void
    {
        // The whitespace and empty values would be changed by the TrimStrings and ConvertEmptyStringsToNull middleware
        $body = 'AccountSid=AC123&Body=+Hello+&From=%2B15558675310&ToCity=';

        $this->sendForm('POST', '/twilio/webhook', $body, $this->sign('api_token', '/twilio/webhook', $body))
            ->assertOk();
    }

    public function testARequestWithAnInvalidSignatureIsRejected(): void
    {
        $this->sendForm('POST', '/twilio/webhook', 'Body=Hello', 'invalid')
            ->assertForbidden();
    }

    public function testARequestWithoutASignatureIsRejected(): void
    {
        $this->sendForm('POST', '/twilio/webhook', 'Body=Hello', null)
            ->assertForbidden();
    }

    public function testARequestWithAChangedBodyIsRejected(): void
    {
        $this->sendForm('POST', '/twilio/webhook', 'Body=Goodbye', $this->sign('api_token', '/twilio/webhook', 'Body=Hello'))
            ->assertForbidden();
    }

    public function testTheQueryStringIsValidatedAsItWasSent(): void
    {
        // The request's full URL would sort the query string
        $uri = '/twilio/webhook?b=2&a=1';

        $this->sendForm('POST', $uri, 'Body=Hello', $this->sign('api_token', $uri, 'Body=Hello'))
            ->assertOk();
    }

    public function testAGetRequestIsValidated(): void
    {
        $uri = '/twilio/webhook?CallSid=CA123&From=%2B15558675310';

        $this->sendForm('GET', $uri, '', $this->sign('api_token', $uri, ''))
            ->assertOk();
    }

    public function testAJsonRequestIsValidatedWithTheBodyHash(): void
    {
        $body = '{"event":"message.delivered","sid":"SM123"}';
        $uri = '/twilio/webhook?bodySHA256=' . RequestValidator::computeBodyHash($body);
        $signature = (new RequestValidator('api_token'))->computeSignature('http://localhost' . $uri);

        $this->sendJson($uri, $body, $signature)->assertOk();
        $this->sendJson($uri, '{"event":"message.failed","sid":"SM123"}', $signature)->assertForbidden();
    }

    public function testRequestsCanBeValidatedWithAnotherConnection(): void
    {
        $this->sendForm('POST', '/twilio/other', 'Body=Hello', $this->sign('other_token', '/twilio/other', 'Body=Hello'))
            ->assertOk();

        $this->sendForm('POST', '/twilio/other', 'Body=Hello', $this->sign('api_token', '/twilio/other', 'Body=Hello'))
            ->assertForbidden();
    }

    public function testTheWebhookTokenIsUsedInsteadOfTheToken(): void
    {
        $this->sendForm('POST', '/twilio/api-key', 'Body=Hello', $this->sign('auth_token', '/twilio/api-key', 'Body=Hello'))
            ->assertOk();

        $this->sendForm('POST', '/twilio/api-key', 'Body=Hello', $this->sign('api_key_secret', '/twilio/api-key', 'Body=Hello'))
            ->assertForbidden();
    }

    public function testAConnectionWithoutATokenCannotValidateRequests(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->withoutExceptionHandling();

        $this->sendForm('POST', '/twilio/no-token', 'Body=Hello', $this->sign('', '/twilio/no-token', 'Body=Hello'));
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('twilio.connections.twilio', ['sid' => 'AC123', 'token' => 'api_token']);
        $app['config']->set('twilio.connections.other', ['sid' => 'AC456', 'token' => 'other_token']);
        $app['config']->set('twilio.connections.api_key', ['sid' => 'SK123', 'token' => 'api_key_secret', 'account_sid' => 'AC123', 'webhook_token' => 'auth_token']);
        $app['config']->set('twilio.connections.no_token', ['sid' => 'AC789', 'token' => '']);
    }

    /**
     * @param Router $router
     */
    protected function defineRoutes($router): void
    {
        $router->match(['GET', 'POST'], '/twilio/webhook', static fn(): string => 'OK')->middleware(ValidateTwilioSignature::class);
        $router->post('/twilio/other', static fn(): string => 'OK')->middleware(ValidateTwilioSignature::using('other'));
        $router->post('/twilio/api-key', static fn(): string => 'OK')->middleware(ValidateTwilioSignature::using('api_key'));
        $router->post('/twilio/no-token', static fn(): string => 'OK')->middleware(ValidateTwilioSignature::using('no_token'));
    }

    /**
     * @return class-string<ServiceProvider>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TwilioProvider::class,
        ];
    }

    private function sign(string $token, string $uri, string $body): string
    {
        return (new RequestValidator($token))->computeSignature('http://localhost' . $uri, Query::parse($body));
    }

    private function sendForm(string $method, string $uri, string $body, ?string $signature): TestResponse
    {
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];

        if ($signature !== null) {
            $server['HTTP_X_TWILIO_SIGNATURE'] = $signature;
        }

        // Like PHP, a POST request's parameters are also parsed from the body
        return $this->call($method, $uri, $method === 'POST' ? Query::parse($body) : [], [], [], $server, $body);
    }

    private function sendJson(string $uri, string $body, string $signature): TestResponse
    {
        return $this->call('POST', $uri, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_TWILIO_SIGNATURE' => $signature], $body);
    }
}
