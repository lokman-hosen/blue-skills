<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\MessageSide;
use App\Domain\Messaging\Events\MessageSent;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Posts a message, marks the conversation read for the sender, and tells the other side
 * (live through Reverb, and as a notification).
 */
class SendMessage
{
    public function __construct(private readonly MarkConversationRead $markRead) {}

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function handle(Conversation $conversation, User $sender, string $body, array $attachments = []): Message
    {
        $message = DB::transaction(function () use ($conversation, $sender, $body, $attachments): Message {
            /** @var Message $message */
            $message = $conversation->messages()->create([
                'sender_user_id' => $sender->id,
                'side' => $sender->isWorker() ? MessageSide::Worker : MessageSide::Organization,
                'body' => $body,
            ]);

            foreach ($attachments as $file) {
                $message->addMedia($file)
                    ->usingName($file->getClientOriginalName())
                    ->usingFileName('attachment.'.($file->guessExtension() ?? 'pdf'))
                    ->toMediaCollection(Message::ATTACHMENTS);
            }

            $conversation->update(['last_message_at' => $message->created_at]);
            $this->markRead->handle($conversation, $sender);

            return $message;
        });

        MessageSent::dispatch($message);

        return $message;
    }
}
