<?php

namespace Database\Factories;

use App\Domain\Crm\Enums\ContactChannel;
use App\Domain\Crm\Enums\ContactOutcome;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadContactAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadContactAttempt>
 */
class LeadContactAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'channel' => ContactChannel::PhoneCall,
            'outcome' => ContactOutcome::Reached,
            'note' => 'Explained how to finish the Skill Passport.',
            'attempted_at' => now(),
        ];
    }
}
