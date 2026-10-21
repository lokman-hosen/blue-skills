<?php

namespace Database\Factories;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\DeliveryChannel;
use App\Domain\Messaging\Enums\DeliveryStatus;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Models\OutboundMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutboundMessage>
 */
class OutboundMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->worker(),
            'channel' => DeliveryChannel::WhatsApp,
            'recipient' => '+8801711000099',
            'event' => NotificationEvent::Shortlisted,
            'payload' => ['params' => ['job' => 'Electrician', 'org' => 'ABC Contracting'], 'url' => '/applications'],
            'status' => DeliveryStatus::Queued,
        ];
    }
}
