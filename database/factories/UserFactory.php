<?php

namespace Database\Factories;

use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => UserType::Org,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'locale' => 'en',
            'timezone' => 'UTC',
            'data_region' => config('blueskills.default_data_region'),
        ];
    }

    /**
     * Workers sign in with a phone OTP; email and password are optional.
     */
    public function worker(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserType::Worker,
            'phone' => '+8801'.fake()->unique()->numerify('7########'),
            'phone_verified_at' => now(),
            'email' => null,
            'email_verified_at' => null,
            'password' => null,
            'locale' => 'bn',
            'timezone' => 'Asia/Dhaka',
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserType::Staff,
            'timezone' => 'Asia/Dubai',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
