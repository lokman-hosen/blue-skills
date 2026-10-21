<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Applications\Models\Application;
use App\Domain\Organization\Models\Organization;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The secure conversation of one application, between the worker and the organization that
 * posted the job (spec §9). The worker's phone number is never part of it.
 *
 * @property Carbon|null $last_message_at
 */
#[Fillable(['application_id', 'organization_id', 'worker_profile_id', 'last_message_at'])]
#[UseFactory(ConversationFactory::class)]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, HasUlids;

    /** Days after an application closes during which both sides can still write. */
    public const OPEN_DAYS_AFTER_CLOSE = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasMany<ConversationRead, $this>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(ConversationRead::class);
    }

    /**
     * Read-only once the application has been closed for OPEN_DAYS_AFTER_CLOSE days.
     */
    public function isWritable(): bool
    {
        $application = $this->application;

        if (! $application->isClosed()) {
            return true;
        }

        $closedAt = $application->stage_changed_at ?? $application->updated_at;

        return $closedAt === null || $closedAt->greaterThan(now()->subDays(self::OPEN_DAYS_AFTER_CLOSE));
    }
}
