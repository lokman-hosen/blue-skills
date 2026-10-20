<?php

namespace Database\Factories;

use App\Domain\Config\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->countryCode(),
            'name' => fake()->country(),
            'currency' => fake()->currencyCode(),
            'dial_code' => '+'.fake()->numberBetween(1, 998),
            'enabled_as_source' => false,
            'enabled_as_destination' => true,
        ];
    }

    /**
     * Code of an existing country, creating one when the table is empty. Use for
     * char(2) foreign keys, which reference countries.code rather than the ULID.
     */
    public static function existingCode(): string
    {
        return Country::query()->inRandomOrder()->value('code') ?? Country::factory()->create()->code;
    }
}
