<?php

namespace Database\Factories;

use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Specialization>
 */
class SpecializationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'occupation_id' => Occupation::factory(),
            'slug' => Str::slug($name),
            'name' => ['en' => Str::title($name)],
        ];
    }
}
