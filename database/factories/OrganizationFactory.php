<?php

namespace Database\Factories;

use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Unverified;
use App\Domain\Organization\States\Verification\Verified;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'type' => OrganizationType::Employer,
            'legal_name' => $name,
            'display_name' => $name,
            'country_code' => fn () => CountryFactory::existingCode(),
            'city' => fake()->city(),
            'registration_number' => fake()->bothify('REG-####-??'),
            'verification_status' => Unverified::class,
            'data_region' => config('blueskills.default_data_region'),
        ];
    }

    public function employer(): static
    {
        return $this->state(['type' => OrganizationType::Employer]);
    }

    public function agency(): static
    {
        return $this->state(fn () => [
            'type' => OrganizationType::Agency,
            'licence_number' => fake()->bothify('RA-####-####'),
            'licence_expires_on' => now()->addYear(),
        ]);
    }

    public function institute(): static
    {
        return $this->state(['type' => OrganizationType::Institute]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verification_status' => Verified::class, 'verified_at' => now()]);
    }
}
