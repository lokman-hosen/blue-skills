<?php

namespace Database\Factories;

use App\Domain\Worker\Enums\LanguageProficiency;
use App\Domain\Worker\Models\WorkerLanguage;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerLanguage>
 */
class WorkerLanguageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'worker_profile_id' => WorkerProfile::factory(),
            'language_code' => fn () => LanguageFactory::existingCode(),
            'proficiency' => LanguageProficiency::Fluent,
        ];
    }
}
