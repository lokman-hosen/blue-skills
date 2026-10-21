<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\MessageSide;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A message in a conversation. Messages are never edited; attachments are private files that
 * only the conversation's parties can open.
 */
#[Fillable(['conversation_id', 'sender_user_id', 'side', 'body'])]
#[UseFactory(MessageFactory::class)]
class Message extends Model implements HasMedia
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasUlids, InteractsWithMedia;

    /** Microsecond timestamps drive unread counts. */
    protected $dateFormat = 'Y-m-d H:i:s.uP';

    public const ATTACHMENTS = 'attachments';

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Messages are never edited.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['side' => MessageSide::class];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ATTACHMENTS)->useDisk(config('blueskills.private_disk'));
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
