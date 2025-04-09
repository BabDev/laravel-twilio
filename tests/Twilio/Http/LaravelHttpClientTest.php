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
