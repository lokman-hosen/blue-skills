<?php

namespace Database\Factories;

use App\Domain\Config\Enums\LanguageStatus;
use App\Domain\Config\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->languageCode(),
            'native_name' => fake()->word(),
            'english_name' => fake()->word(),
            'rtl' => false,
            'status' => LanguageStatus::Active,
        ];
    }

    /**
     * Code of an existing language, creating one when the table is empty.
     */
    public static function existingCode(): string
    {
        return Language::query()->inRandomOrder()->value('code') ?? Language::factory()->create()->code;
    }
}
