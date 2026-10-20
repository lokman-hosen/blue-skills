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
        'max_per_ip_per_hour' => 20,
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

];
