<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // WhatsApp Cloud API (Meta) for worker sign-in codes. Needs an approved
    // "authentication" template with one body parameter and a copy-code button.
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'otp_template' => env('WHATSAPP_OTP_TEMPLATE', 'blueskills_login_code'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v23.0'),
        // Module 5: one approved "utility" template with two body parameters (job, link).
        'update_template' => env('WHATSAPP_UPDATE_TEMPLATE', 'blueskills_update'),
    ],

    // Twilio Programmable Messaging for worker SMS (Module 5, D-067), called over HTTP.
    // `senders` maps a country code to its sender (alphanumeric ID or number), e.g.
    // TWILIO_SENDERS='{"BD":"+1555…","NP":"BLUESKILLS"}'; `from` is used for anything else.
    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        'senders' => json_decode((string) env('TWILIO_SENDERS', '{}'), true) ?: [],
    ],

];
