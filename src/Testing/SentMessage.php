<?php

namespace BabDev\Twilio\Testing;

final readonly class SentMessage
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $connection,
        public string $to,
        public string $message,
        public array $params,
    ) {}
}
