<?php

namespace App\Domain\Messaging\Events;

use App\Domain\Messaging\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells open threads and unread badges that a message arrived. The payload carries ids only;
 * pages reload what the viewer is allowed to see.
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;

        return [
            new PrivateChannel("conversations.{$conversation->id}"),
            new PrivateChannel("organizations.{$conversation->organization_id}.inbox"),
            new PrivateChannel("users.{$conversation->workerProfile->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return ['conversation' => $this->message->conversation_id, 'message' => $this->message->id];
    }
}
