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

];
