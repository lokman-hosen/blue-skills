<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\ConversationRead;

class MarkConversationRead
{
    public function handle(Conversation $conversation, User $user): void
    {
        ConversationRead::query()->updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            ['last_read_at' => now()],
        );
    }
}
