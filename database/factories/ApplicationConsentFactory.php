<?php

namespace Database\Factories;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationConsent>
 */
class ApplicationConsentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'item' => ApplicationConsentItem::SkillPassport,
            'granted' => true,
        ];
    }
}
