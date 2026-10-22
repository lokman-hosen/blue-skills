<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * The first staff superuser of a new installation (D-108), from `blueskills:create-superuser`.
 * The password follows the production rules; two-factor is set up at the first sign-in
 * (staff screens require it, D-035). Other staff are invited from Admin › Staff.
 */
class CreateSuperuser
{
    public function handle(string $name, string $email, string $password): User
    {
        Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
                'password' => ['required', Password::defaults()],
            ],
        )->validate();

        return DB::transaction(function () use ($name, $email, $password): User {
            $user = User::query()->create([
                'type' => UserType::Staff,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'timezone' => 'Asia/Dubai',
                'data_region' => config('blueskills.default_data_region'),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignStaffRole(StaffRole::Superuser);

            activity('identity')->performedOn($user)->log('staff.superuser_created');

            return $user;
        });
    }
}
