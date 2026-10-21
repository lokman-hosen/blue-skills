<?php

namespace Database\Factories;

use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Trust\Enums\TrustActionType;
use App\Domain\Trust\Enums\TrustReason;
use App\Domain\Trust\Models\TrustAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrustAction>
 */
class TrustActionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => 'job',
            'subject_id' => JobPosting::factory()->published(),
            'action' => TrustActionType::Suspend,
            'reason_code' => TrustReason::UnauthorizedPayment,
        ];
    }
}
