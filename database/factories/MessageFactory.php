<?php

namespace Database\Factories;

use App\Domain\Messaging\Enums\MessageSide;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_user_id' => null,
            'side' => MessageSide::Organization,
            'body' => fake()->sentence(),
        ];
    }
}
