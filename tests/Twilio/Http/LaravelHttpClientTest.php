<?php

namespace BabDev\Twilio\Tests\Twilio\Http;

use BabDev\Twilio\Twilio\Http\LaravelHttpClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Orchestra\Testbench\TestCase;
use Twilio\AuthStrategy\BasicAuthStrategy;
use Twilio\Exceptions\HttpException;

final class LaravelHttpClientTest extends TestCase
{
    public function testARequestCanBeSentToTheTwilioApiWithoutCredentials(): void
    {
        $url         = 'https://api.twilio.com/2010-04-01/Accounts/SID/Messages.json';
        $headers     = [];
        $messageData = [
            'From' => '+16512432364',
            'To'   => '+18003285920',
            'Body' => 'Test Message',
        ];

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            $url => $factory->response('', 200, []),
        ]);

        (new LaravelHttpClient($factory))->request(
            'POST',
            $url,
            [],
            $messageData,
            $headers
        );

        $factory->assertSent(
            static fn(Request $request, Response $response): bool => !$request->hasHeader('Authorization')
                && $request->url() === $url
                && $request->data() === $messageData
        );
    }

    public function testARequestCanBeSentToTheTwilioApiWithCredentials(): void
    {
        $url         = 'https://api.twilio.com/2010-04-01/Accounts/SID/Messages.json';
        $headers     = [];
        $messageData = [
            'From' => '+16512432364',
            'To'   => '+18003285920',
            'Body' => 'Test Message',
        ];

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            $url => $factory->response('', 200, []),
        ]);

        (new LaravelHttpClient($factory))->request(
            'POST',
            $url,
            [],
            $messageData,
            $headers,
            'username',
            'password'
        );

        $factory->assertSent(
            static fn(Request $request, Response $response): bool => $request->hasHeader('Authorization')
                && $request->url() === $url
                && $request->data() === $messageData
        );
    }

    public function testARequestCanBeSentToTheTwilioApiWithAuthStrategy(): void
    {
        $url         = 'https://api.twilio.com/2010-04-01/Accounts/SID/Messages.json';
        $headers     = [];
        $messageData = [
            'From' => '+16512432364',
            'To'   => '+18003285920',
            'Body' => 'Test Message',
        ];

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            $url => $factory->response('', 200, []),
        ]);

        (new LaravelHttpClient($factory))->request(
            'POST',
            $url,
            [],
            $messageData,
            $headers,
            null,
            null,
            null,
            new BasicAuthStrategy('username', 'password'),
        );

        $factory->assertSent(
            static fn(Request $request, Response $response): bool => $request->hasHeader('Authorization')
                && $request->url() === $url
                && $request->data() === $messageData
        );
    }

    public function testListValuesAreEncodedAsRepeatedKeys(): void
    {
        $url = 'https://api.twilio.com/2010-04-01/Accounts/SID/Calls.json';

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            'api.twilio.com/*' => $factory->response('', 200, []),
        ]);

        (new LaravelHttpClient($factory))->request(
            'POST',
            $url,
            ['PageSize' => 20, 'Status' => ['queued', 'ringing']],
            ['To' => '+18003285920', 'StatusCallbackEvent' => ['initiated', 'ringing']],
        );

        $factory->assertSent(
            static fn(Request $request, Response $response): bool => $request->url() === $url . '?PageSize=20&Status=queued&Status=ringing'
                && $request->body() === 'To=%2B18003285920&StatusCallbackEvent=initiated&StatusCallbackEvent=ringing'
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
        );
    }

    public function testAGetRequestIsSentWithoutABody(): void
    {
        $url = 'https://api.twilio.com/2010-04-01/Accounts/SID/Messages.json';

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            'api.twilio.com/*' => $factory->response('', 200, []),
        ]);

        (new LaravelHttpClient($factory))->request('GET', $url, ['To' => '+18003285920']);

        $factory->assertSent(
            static fn(Request $request, Response $response): bool => $request->url() === $url . '?To=%2B18003285920'
                && $request->body() === ''
                && !$request->hasHeader('Content-Type')
        );
    }

    public function testTheTimeoutIsApplied(): void
    {
        $url     = 'https://api.twilio.com/2010-04-01/Accounts/SID/Messages.json';
        $timeout = null;

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake(
            static function (Request $request, array $options) use ($factory, &$timeout) {
                $timeout = $options['timeout'] ?? null;

                return $factory->response('', 200, []);
            }
        );

        (new LaravelHttpClient($factory))->request('POST', $url, [], [], [], 'username', 'password', 5);

        $this->assertSame(5, $timeout);
    }

    public function testRedirectsAreNotFollowed(): void
    {
        $url = 'https://api.twilio.com/2010-04-01/Accounts/SID/Messages.json';

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            'api.twilio.com/*' => $factory->response('', 302, ['Location' => 'https://example.com/elsewhere']),
            'example.com/*' => $factory->response('', 200, []),
        ]);

        $response = (new LaravelHttpClient($factory))->request('POST', $url, [], [], [], 'username', 'password');

        $this->assertSame(302, $response->getStatusCode());
        $factory->assertSentCount(1);
    }

    public function testAnExceptionIsThrownWhenThereIsAnErrorPerformingTheRequest(): void
    {
        $this->expectException(HttpException::class);

        $url         = 'https://api.twilio.com/2010-04-01/Accounts/SID/Messages.json';
        $headers     = [];
        $messageData = [
            'From' => '+16512432364',
            'To'   => '+18003285920',
            'Body' => 'Test Message',
        ];

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            $url => static function (): void {
                throw new \RuntimeException('Testing');
            },
        ]);

        (new LaravelHttpClient($factory))->request(
            'POST',
            $url,
            [],
            $messageData,
            $headers,
            'username',
            'password'
        );
    }
}
