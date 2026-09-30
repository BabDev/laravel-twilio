<?php

namespace BabDev\Twilio\Notifications\Messages;

final class TwilioMessage
{
    /**
     * @var array<string, mixed>
     */
    private array $parameters = [];

    public function __construct(
        private string $content = '',
    ) {}

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function from(string $from): self
    {
        $this->parameters['from'] = $from;

        return $this;
    }

    public function messagingService(string $messagingServiceSid): self
    {
        $this->parameters['messagingServiceSid'] = $messagingServiceSid;

        return $this;
    }

    /**
     * Add media to the message, sending it as an MMS.
     */
    public function mediaUrl(string ...$urls): self
    {
        foreach ($urls as $url) {
            $this->parameters['mediaUrl'][] = $url;
        }

        return $this;
    }

    /**
     * Send the message using a Content Template, with optional values for the template's variables.
     *
     * @param array<string, string> $variables
     *
     * @throws \JsonException if the variables cannot be encoded
     */
    public function template(string $contentSid, array $variables = []): self
    {
        $this->parameters['contentSid'] = $contentSid;

        if ($variables === []) {
            unset($this->parameters['contentVariables']);
        } else {
            $this->parameters['contentVariables'] = json_encode($variables, \JSON_THROW_ON_ERROR);
        }

        return $this;
    }

    public function statusCallback(string $url): self
    {
        $this->parameters['statusCallback'] = $url;

        return $this;
    }

    /**
     * Schedule the message to be sent at a later time, which requires sending through a Messaging Service.
     */
    public function sendAt(\DateTimeInterface $sendAt): self
    {
        $this->parameters['sendAt'] = \DateTimeImmutable::createFromInterface($sendAt)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
        $this->parameters['scheduleType'] = 'fixed';

        return $this;
    }

    /**
     * Set any other parameters supported by the SDK when creating a message, overriding parameters already set.
     *
     * @param array<string, mixed> $parameters
     */
    public function with(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
