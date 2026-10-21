<?php

namespace App\Domain\Messaging\Data;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class ConversationListItemData extends Data
{
    public function __construct(
        public string $id,
        public string $jobTitle,
        public string $jobCode,
        /** The organization for workers; the worker for organization members. */
        public string $counterpart,
        public ?string $preview,
        public ?string $lastMessageAt,
        public int $unread,
    ) {}

    public static function fromConversation(Conversation $conversation, User $viewer): self
    {
        $job = $conversation->application->job;
        /** @var Message|null $last */
        $last = $conversation->messages()->reorder()->latest()->latest('id')->first();

        return new self(
            id: $conversation->id,
            jobTitle: $job->title,
            jobCode: $job->public_code,
            counterpart: $viewer->isWorker() ? $conversation->organization->display_name : $conversation->workerProfile->user->name,
            preview: $last !== null ? Str::limit($last->body, 90) : null,
            lastMessageAt: $conversation->last_message_at?->toIso8601String(),
            unread: (int) ($conversation->getAttribute('unread_count') ?? 0),
        );
    }
}
