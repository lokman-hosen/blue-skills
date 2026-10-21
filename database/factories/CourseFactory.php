<?php

namespace Database\Factories;

use App\Domain\Institute\Models\Course;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->institute()->verified(),
            'title' => 'Electrical Installation',
            'duration_weeks' => 24,
        ];
    }
}
