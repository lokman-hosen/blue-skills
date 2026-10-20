<?php

namespace Database\Factories;

use App\Domain\Config\Enums\CountryRuleKey;
use App\Domain\Config\Models\CountryRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CountryRule>
 */
class CountryRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_code' => fn () => CountryFactory::existingCode(),
            'key' => fake()->randomElement(CountryRuleKey::cases()),
            'value' => ['text' => fake()->sentence()],
        ];
    }
}
