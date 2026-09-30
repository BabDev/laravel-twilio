<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the Twilio connections below you wish
    | to use as your default connection for your application. You may use
    | many connections at once using this library.
    |
    */

    'default' => env('TWILIO_CONNECTION', 'twilio'),

    /*
    |--------------------------------------------------------------------------
    | Notification Channel Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the Twilio connections below you wish
    | to use as the connection when using Laravel's notifications system.
    | By default, this uses your default connection.
    |
    */

    'notification_channel' => env('TWILIO_NOTIFICATION_CHANNEL_CONNECTION', env('TWILIO_CONNECTION', 'twilio')),

    /*
    |--------------------------------------------------------------------------
    | Twilio Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the Twilio connections setup for your application.
    | Each connection is required to provide a "sid" and "token" key, which
    | are required parameters for creating a connection to the Twilio REST
    | API, and a "from" key with the default number to send from. The "from"
    | key is optional when "messaging_service_sid" is set and the connection
    | is only used to send messages.
    |
    | When authenticating with an API key, the "sid" and "token" are the API
    | key's SID and secret, and the "account_sid" key must be set to the SID
    | of the account the key belongs to. The optional "region" and "edge"
    | keys route requests through a specific Twilio region and edge location.
    |
    | Set the optional "messaging_service_sid" key to send messages through a
    | Messaging Service, letting Twilio choose the sender from its pool. The
    | "from" number is still used for calls, and when sending a message with
    | a custom "from" number.
    |
    | To create a new connection, duplicate the "twilio" connection
    | configuration as a new entry in your connections array and give
    | it a unique name. You can now access this connection through the
    | connection manager.
    |
    */

    'connections' => [
        'twilio' => [
            'sid' => env('TWILIO_API_SID', ''),
            'token' => env('TWILIO_API_AUTH_TOKEN', ''),
            'from' => env('TWILIO_API_FROM_NUMBER', ''),
            'account_sid' => env('TWILIO_API_ACCOUNT_SID'),
            'region' => env('TWILIO_API_REGION'),
            'edge' => env('TWILIO_API_EDGE'),
            'messaging_service_sid' => env('TWILIO_API_MESSAGING_SERVICE_SID'),
        ],
    ],

];
