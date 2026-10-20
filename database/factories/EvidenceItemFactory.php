<?php

namespace Database\Factories;

use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\SelfDeclared;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvidenceItem>
 */
class EvidenceItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'worker_profile_id' => WorkerProfile::factory(),
            'type' => EvidenceType::WorkExperience,
            'status' => SelfDeclared::class,
        ];
    }
}
