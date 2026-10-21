<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\DeliveryChannel;
use App\Domain\Messaging\Enums\DeliveryStatus;
use App\Domain\Messaging\Enums\NotificationEvent;
use Database\Factories\OutboundMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One WhatsApp, SMS or email delivery, kept for audit and retries. The recipient is encrypted.
 *
 * @property Carbon|null $send_after
 * @property Carbon|null $sent_at
 */
#[Fillable(['user_id', 'channel', 'recipient', 'event', 'template', 'status', 'provider_id', 'error', 'attempts', 'send_after', 'sent_at'])]
#[UseFactory(OutboundMessageFactory::class)]
class OutboundMessage extends Model
{
    /** @use HasFactory<OutboundMessageFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => DeliveryChannel::class,
            'recipient' => 'encrypted',
            'event' => NotificationEvent::class,
            'status' => DeliveryStatus::class,
            'attempts' => 'integer',
            'send_after' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
