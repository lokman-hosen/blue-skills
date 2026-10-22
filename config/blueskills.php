<?php

return [

    /*
    | Data residency region stamped on new users and organizations.
    */
    'default_data_region' => env('BLUESKILLS_DEFAULT_DATA_REGION', 'ae'),

    /*
    | Disk for sensitive files (passport scans, IDs, contracts, SkillShow and voice media).
    | Files are only ever served through signed, short-lived URLs.
    */
    'private_disk' => env('BLUESKILLS_PRIVATE_DISK', 'private'),

    /*
    | The first staff superuser, created by SuperuserSeeder. In production both values must
    | be set or the seeder skips the account.
    */
    'superuser' => [
        'name' => env('BLUESKILLS_SUPERUSER_NAME', 'BLUESKILLS Superuser'),
        'email' => env('BLUESKILLS_SUPERUSER_EMAIL'),
        'password' => env('BLUESKILLS_SUPERUSER_PASSWORD'),
    ],

    /*
    | Worker phone sign-in codes (Module 1). The channel is `whatsapp` in production and `log`
    | locally, where the code is written to the application log instead of being sent.
    */
    'otp' => [
        'channel' => env('OTP_CHANNEL', 'log'),
        'length' => 6,
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        'resend_seconds' => 60,
        'max_per_phone_per_hour' => 5,
        // One job-fair Wi-Fi network is one IP address; raise this for a big event (D-105).
        'max_per_ip_per_hour' => (int) env('BLUESKILLS_OTP_MAX_PER_IP_PER_HOUR', 100),
    ],

    /*
    | Alerts outside the app (Module 5, D-067). Drivers are `log` locally; production uses
    | `cloud` (WhatsApp Cloud API) and `twilio`. Workers get no WhatsApp or SMS during quiet
    | hours in their own time zone; those wait until the end of quiet hours.
    */
    'notifications' => [
        'whatsapp_driver' => env('NOTIFY_WHATSAPP_DRIVER', 'log'),
        'sms_driver' => env('NOTIFY_SMS_DRIVER', 'log'),
        'quiet_from' => 22,
        'quiet_until' => 7,
    ],

    /*
    | Voice-to-CV (Module 2, D-047). `speech_to_text` is `sample` locally (returns the
    | prototype's answers) or `none` (typing only) until a provider driver is added.
    | Recordings are deleted once the answer is confirmed or discarded, and after
    | `recording_retention_days` at the latest.
    */
    'voice' => [
        'speech_to_text' => env('SPEECH_TO_TEXT_DRIVER', 'sample'),
        'max_recording_seconds' => 60,
        'max_recording_kb' => 5_120,
        'recording_retention_days' => 30,
        'max_typed_characters' => 500,
    ],

    /*
    | Countries whose numbers can register as workers (dial-code picker on the phone step).
    */
    'worker_phone_countries' => ['BD', 'NP', 'IN', 'PK', 'LK'],

    /*
    | Free webmail domains. Organizations may register with them, but the verification request
    | is flagged for the reviewer (D-036).
    */
    'free_email_domains' => [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.in', 'ymail.com', 'outlook.com',
        'hotmail.com', 'live.com', 'msn.com', 'icloud.com', 'me.com', 'aol.com', 'proton.me',
        'protonmail.com', 'gmx.com', 'mail.com', 'yandex.com', 'zoho.com',
    ],

    /*
    | Staff must confirm two-factor authentication before using admin screens (D-035). Only
    | switch this off on a local machine.
    */
    'enforce_staff_two_factor' => (bool) env('BLUESKILLS_ENFORCE_STAFF_2FA', true),

    /*
    | Response headers (D-105). The Content Security Policy is enforced in production and sent
    | report-only elsewhere, so a blocked source shows up in the browser console first. Extra
    | sources are space separated: `media_sources` for a private disk on another host (S3
    | temporary URLs), `connect_sources` for anything the browser calls besides this app and
    | Reverb. HSTS is only sent over HTTPS.
    */
    'security' => [
        'csp_enforce' => (bool) env('BLUESKILLS_CSP_ENFORCE', env('APP_ENV') === 'production'),
        'csp_report_uri' => env('BLUESKILLS_CSP_REPORT_URI'),
        'media_sources' => env('BLUESKILLS_CSP_MEDIA_SOURCES', ''),
        'connect_sources' => env('BLUESKILLS_CSP_CONNECT_SOURCES', ''),
        // Where the browser reaches Reverb: the VITE_ values built into the frontend, which can
        // differ from the server-side REVERB_HOST (in Sail that one is the container name).
        'reverb' => [
            'host' => env('VITE_REVERB_HOST', env('REVERB_HOST')),
            'port' => env('VITE_REVERB_PORT', env('REVERB_PORT', 443)),
            'scheme' => env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https')),
        ],
        'hsts_max_age' => (int) env('BLUESKILLS_HSTS_MAX_AGE', 31536000),
        // Writes (POST/PUT/PATCH/DELETE) per minute that routes without their own limit allow.
        'writes_per_minute' => (int) env('BLUESKILLS_WRITES_PER_MINUTE', 120),
        'guest_writes_per_minute' => (int) env('BLUESKILLS_GUEST_WRITES_PER_MINUTE', 300),
    ],

    /*
    | Platform billing (D-095). Limits are tracked but not enforced until pricing is approved:
    | with `enforce` on, publishing a job uses a posting credit and seats and talent search follow
    | the plan. Invoices are settled by hand until a payment provider is added.
    */
    'billing' => [
        'enforce' => (bool) env('BLUESKILLS_BILLING_ENFORCE', false),
        'currency' => 'AED',
        // Basis points: 500 = 5% UAE VAT.
        'vat_rate_bp' => 500,
        'payment_days' => 14,
        'provider' => env('BILLING_PROVIDER', 'manual'),
        // Shown on invoices until online payment exists, e.g. bank account details.
        'payment_instructions' => env('BILLING_PAYMENT_INSTRUCTIONS'),
    ],

];
