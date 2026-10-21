<?php

namespace Database\Factories;

use App\Domain\Trust\Enums\AppealStatus;
use App\Domain\Trust\Models\Appeal;
use App\Domain\Trust\Models\TrustAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appeal>
 */
class AppealFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trust_action_id' => TrustAction::factory(),
            'statement' => 'The fee was the government visa fee, and we refunded it.',
            'status' => AppealStatus::Submitted,
        ];
    }
}
