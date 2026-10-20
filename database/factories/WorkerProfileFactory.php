<?php

namespace Database\Factories;

use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerProfile>
 */
class WorkerProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->worker(),
            'headline' => fake()->jobTitle(),
            'occupation_id' => Occupation::factory(),
            'years_experience' => fake()->numberBetween(1, 15),
            'current_country_code' => fn () => CountryFactory::existingCode(),
            'passport_status' => PassportStatus::Ready,
            'availability' => Availability::Immediately,
            'visibility' => ProfileVisibility::EmployersAndAgencies,
        ];
    }
}
