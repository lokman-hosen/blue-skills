<?php

namespace Database\Factories;

use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'occupation_id' => Occupation::factory(),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'name' => ['en' => Str::title($name)],
        ];
    }
}
