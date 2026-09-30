<?php

namespace BabDev\Twilio\Testing;

final readonly class PlacedCall
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $connection,
        public string $to,
        public array $params,
    ) {}
}
