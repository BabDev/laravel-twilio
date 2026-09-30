# Changelog

## 3.2.0 (2026-09-30)

- Drop support for Laravel 11, Laravel 12.61.1 or 13.13 and later are now required
- Add `account_sid`, `region`, and `edge` connection settings, allowing connections to authenticate with an API key and to send requests through a specific Twilio region and edge location
- Add a `messaging_service_sid` connection setting to send messages through a Messaging Service
- Add the `BabDev\Twilio\Http\Middleware\ValidateTwilioSignature` middleware to validate the signatures of webhook requests from Twilio, with a `webhook_token` connection setting for connections using an API key
- Add `TwilioClient::fake()` to replace the client with a fake for testing
- The `from` connection setting is now optional for connections which only send messages through a Messaging Service
- Passing a `messagingServiceSid` parameter to `TwilioClient::message()` no longer adds the connection's default "from" number, letting Twilio choose the sender from the Messaging Service's sender pool
- `TwilioClient::call()` and `TwilioClient::message()` now throw a `Twilio\Exceptions\ConfigurationException` when there is no sender, instead of sending the request with an empty "from" number
- `TwilioClient::message()` no longer sends an empty message body, allowing messages with only media or a Content Template
- Notifications can return a `BabDev\Twilio\Notifications\Messages\TwilioMessage` from their `toTwilio()` method to send media, Content Templates, scheduled messages, and other message options
- The notification channel now throws an `InvalidArgumentException` when a notification's `toTwilio()` method returns something other than a string or `TwilioMessage`
- Fix the "twilio" notification channel not being available until the client was resolved
- Fix list parameters not being encoded as repeated keys, as the Twilio API expects
- Fix various conformance issues in the HTTP client
- Fix deprecations on PHP 8.4 and later

## 3.1.0 (2026-03-19)

- Add support for Laravel 13

## 3.0.0 (2025-04-09)

- Consult the UPGRADE guide for changes between 2.x and 3.0
