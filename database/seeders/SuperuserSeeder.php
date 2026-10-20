<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SuperuserSeeder extends Seeder
{
    public const LOCAL_EMAIL = 'superuser@blueskills.test';

    /**
     * Production needs BLUESKILLS_SUPERUSER_EMAIL and _PASSWORD. Locally we fall back to a
     * known test account.
     */
    public function run(): void
    {
        $email = config('blueskills.superuser.email');
        $password = config('blueskills.superuser.password');

        if (blank($email) || blank($password)) {
            if (app()->isProduction()) {
                Log::warning('Superuser not created: set BLUESKILLS_SUPERUSER_EMAIL and BLUESKILLS_SUPERUSER_PASSWORD.');

                return;
            }

            $email = self::LOCAL_EMAIL;
            $password = 'password';
        }

        $user = User::query()->firstOrCreate(['email' => $email], [
            'type' => UserType::Staff,
            'name' => config('blueskills.superuser.name'),
            'password' => $password,
            'timezone' => 'Asia/Dubai',
            'data_region' => config('blueskills.default_data_region'),
        ]);
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        if (! $user->hasStaffRole(StaffRole::Superuser)) {
            $user->assignStaffRole(StaffRole::Superuser);
        }
    }
}
