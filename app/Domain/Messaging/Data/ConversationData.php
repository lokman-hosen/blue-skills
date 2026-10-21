<?php

namespace App\Domain\Messaging\Data;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use Spatie\LaravelData\Data;

/**
 * One thread as the viewer may see it. The worker's phone number is never part of it.
 */
class ConversationData extends Data
{
    /**
     * @param  list<MessageData>  $messages
     */
    public function __construct(
        public string $id,
        public string $applicationId,
        public string $jobTitle,
        public string $jobCode,
        public string $counterpart,
        public bool $writable,
        public bool $canPost,
        public array $messages,
    ) {}

    public static function fromConversation(Conversation $conversation, User $viewer, bool $canPost): self
    {
        $conversation->loadMissing(['application.job', 'organization', 'workerProfile.user', 'messages.sender', 'messages.media']);
        $job = $conversation->application->job;

        return new self(
            id: $conversation->id,
            applicationId: $conversation->application_id,
            jobTitle: $job->title,
            jobCode: $job->public_code,
            counterpart: $viewer->isWorker() ? $conversation->organization->display_name : $conversation->workerProfile->user->name,
            writable: $conversation->isWritable(),
            canPost: $canPost,
            messages: $conversation->messages->map(function (Message $message) use ($conversation, $viewer): MessageData {
                $message->setRelation('conversation', $conversation);

                return MessageData::fromMessage($message, $viewer);
            })->values()->all(),
        );
    }
}
