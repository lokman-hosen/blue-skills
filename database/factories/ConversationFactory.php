<?php

namespace Database\Factories;

use App\Domain\Applications\Models\Application;
use App\Domain\Messaging\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'organization_id' => fn (array $attributes) => Application::query()->findOrFail($attributes['application_id'])->job->organization_id,
            'worker_profile_id' => fn (array $attributes) => Application::query()->findOrFail($attributes['application_id'])->worker_profile_id,
        ];
    }
}
