<?php

namespace App\Domain\Messaging\Data;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\MessageSide;
use App\Domain\Messaging\Models\Message;
use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MessageData extends Data
{
    /**
     * @param  list<AttachmentData>  $attachments
     */
    public function __construct(
        public string $id,
        public MessageSide $side,
        public string $senderName,
        public bool $mine,
        public string $body,
        public string $at,
        public array $attachments,
    ) {}

    /**
     * Workers see "member · organization" for organization messages; members see names.
     */
    public static function fromMessage(Message $message, User $viewer): self
    {
        $conversation = $message->conversation;
        $sender = $message->sender;

        $senderName = match ($message->side) {
            MessageSide::Worker => $conversation->workerProfile->user->name,
            MessageSide::Organization => $viewer->isWorker()
                ? trim(($sender->name ?? '').' · '.$conversation->organization->display_name, ' ·')
                : ($sender->name ?? $conversation->organization->display_name),
            MessageSide::System => 'BLUESKILLS',
        };

        return new self(
            id: $message->id,
            side: $message->side,
            senderName: $senderName,
            mine: $message->sender_user_id === $viewer->id,
            body: $message->body,
            at: $message->created_at?->toIso8601String() ?? '',
            attachments: $message->getMedia(Message::ATTACHMENTS)->map(fn (Media $media) => AttachmentData::fromMedia($media))->values()->all(),
        );
    }
}
