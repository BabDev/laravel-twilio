<?php

namespace BabDev\Twilio\Tests\Twilio\Http;

use BabDev\Twilio\Twilio\Http\LaravelHttpClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Orchestra\Testbench\TestCase;
use Twilio\AuthStrategy\BasicAuthStrategy;
use Twilio\Exceptions\HttpException;
use Twilio\Http\File;

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

    public function testFilesAreSentAsMultipartFormData(): void
    {
        $url = 'https://serverless.twilio.com/v1/Services/ZS/Assets/ZH/Versions';

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            'serverless.twilio.com/*' => $factory->response('', 201, []),
        ]);

        (new LaravelHttpClient($factory))->request(
            'POST',
            $url,
            [],
            [
                'Path' => '/hello.txt',
                'FriendlyName' => '',
                'Tags' => ['first', 'second'],
                'Content' => new File('hello.txt', 'Hello world', 'text/plain'),
            ],
            [],
            'username',
            'password',
        );

        $factory->assertSent(
            static fn(Request $request, Response $response): bool => $request->isMultipart()
                && str_starts_with($request->header('Content-Type')[0], 'multipart/form-data; boundary=')
                && str_contains($request->body(), 'name="Path"')
                && str_contains($request->body(), "\r\n\r\n/hello.txt\r\n")
                && preg_match('/name="FriendlyName"\r\n(?:[^\r\n]+\r\n)*\r\n\r\n/', $request->body()) === 1
                && substr_count($request->body(), 'name="Tags"') === 2
                && str_contains($request->body(), 'name="Content"; filename="hello.txt"')
                && str_contains($request->body(), "Content-Type: text/plain\r\n")
                && str_contains($request->body(), "\r\n\r\nHello world\r\n")
        );
    }

    public function testFileContentsAreReadFromThePathWhenNotProvided(): void
    {
        $url  = 'https://serverless.twilio.com/v1/Services/ZS/Assets/ZH/Versions';
        $path = tempnam(sys_get_temp_dir(), 'twilio-upload-');

        file_put_contents($path, 'Contents from disk');

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            'serverless.twilio.com/*' => $factory->response('', 201, []),
        ]);

        try {
            (new LaravelHttpClient($factory))->request('POST', $url, [], ['Content' => new File($path)]);
        } finally {
            @unlink($path);
        }

        $factory->assertSent(
            static fn(Request $request, Response $response): bool => str_contains($request->body(), \sprintf('name="Content"; filename="%s"', basename($path)))
                && str_contains($request->body(), 'Contents from disk')
        );
    }

    public function testFileContentsCanBeAResource(): void
    {
        $url    = 'https://serverless.twilio.com/v1/Services/ZS/Assets/ZH/Versions';
        $stream = fopen('php://memory', 'r+b');

        fwrite($stream, 'Contents from a stream');
        rewind($stream);

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake([
            'serverless.twilio.com/*' => $factory->response('', 201, []),
        ]);

        (new LaravelHttpClient($factory))->request('POST', $url, [], ['Content' => new File('stream.txt', $stream)]);

        $factory->assertSent(
            static fn(Request $request, Response $response): bool => str_contains($request->body(), 'name="Content"; filename="stream.txt"')
                && str_contains($request->body(), 'Contents from a stream')
        );
    }

    public function testAnExceptionIsThrownWhenFileContentsAreNotSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported content type');

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake();

        (new LaravelHttpClient($factory))->request(
            'POST',
            'https://serverless.twilio.com/v1/Services/ZS/Assets/ZH/Versions',
            [],
            ['Content' => new File('numbers.txt', 12345)],
        );
    }

    public function testAnExceptionIsThrownWhenAFileCannotBeOpened(): void
    {
        $this->expectException(HttpException::class);

        /** @var Factory $factory */
        $factory = $this->app->make(Factory::class);
        $factory->fake();

        (new LaravelHttpClient($factory))->request(
            'POST',
            'https://serverless.twilio.com/v1/Services/ZS/Assets/ZH/Versions',
            [],
            ['Content' => new File('/path/that/does/not/exist.txt')],
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
