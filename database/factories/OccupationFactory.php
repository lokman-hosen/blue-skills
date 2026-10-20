<?php

namespace Database\Factories;

use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Occupation>
 */
class OccupationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'sector_id' => Sector::factory(),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'name' => ['en' => $name],
        ];
    }
}
