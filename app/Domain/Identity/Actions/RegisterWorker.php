<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Worker\Enums\ProfileVisibility;
use Illuminate\Support\Facades\DB;

/**
 * Creates a worker account and an empty Skill Passport after the phone was verified.
 * New profiles are visible only to jobs the worker applies to (D-039).
 */
class RegisterWorker
{
    public function handle(string $phone, string $name, string $countryCode, string $locale): User
    {
        return DB::transaction(function () use ($phone, $name, $countryCode, $locale): User {
            $user = User::query()->create([
                'type' => UserType::Worker,
                'name' => $name,
                'phone' => $phone,
                'locale' => $locale,
                'data_region' => config('blueskills.default_data_region'),
            ]);
            $user->forceFill(['phone_verified_at' => now()])->save();

            $user->workerProfile()->create([
                'current_country_code' => $countryCode,
                'visibility' => ProfileVisibility::ApplicationsOnly,
            ]);

            activity('identity')->performedOn($user)->causedBy($user)->log('worker.registered');

            return $user;
        });
    }
}
