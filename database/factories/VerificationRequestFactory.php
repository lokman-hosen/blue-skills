<?php

namespace Database\Factories;

use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\VerificationRequest\Open;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationRequest>
 */
class VerificationRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => 'organization',
            'subject_id' => Organization::factory(),
            'type' => VerificationType::Organization,
            'status' => Open::class,
        ];
    }
}
