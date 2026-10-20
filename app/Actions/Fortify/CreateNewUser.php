<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        // Email + password sign-up is for organization users; workers use phone OTP (Module 1).
        return User::create([
            'type' => UserType::Org,
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'data_region' => config('blueskills.default_data_region'),
        ]);
    }
}
