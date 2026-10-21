<?php

namespace App\Domain\Messaging\Support;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

/**
 * Unread messages for one person: messages from someone else after the time they last opened
 * the conversation. Each organization member has their own read state.
 */
final class Unread
{
    /**
     * Constrains a messages query to the ones the user has not read.
     *
     * @param  Builder<Message>  $messages
     */
    public static function messages(Builder $messages, User $user): void
    {
        $messages
            ->where(fn (Builder $query) => $query->whereNull('sender_user_id')->orWhere('sender_user_id', '!=', $user->id))
            ->whereRaw(
                "messages.created_at > coalesce((select last_read_at from conversation_reads where conversation_reads.conversation_id = messages.conversation_id and conversation_reads.user_id = ?), '-infinity'::timestamptz)",
                [$user->id],
            );
    }

    /**
     * Conversations with at least one unread message, for the navigation badge.
     */
    public static function conversations(User $user, ?Organization $organization): int
    {
        $query = Conversation::query();

        if ($user->isWorker()) {
            $query->where('worker_profile_id', $user->workerProfile?->id);
        } elseif ($organization !== null) {
            $query->where('organization_id', $organization->id);
        } else {
            return 0;
        }

        return $query->whereHas('messages', fn (Builder $messages) => self::messages($messages, $user))->count();
    }
}
