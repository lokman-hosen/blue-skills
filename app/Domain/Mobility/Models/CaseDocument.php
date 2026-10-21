<?php

namespace App\Domain\Mobility\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\CaseDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One item on a case's document checklist (spec §6). A worker's file is shared with the managing
 * organization (and the employer when allowed) only while the share is active (product rule 6).
 *
 * @property Carbon|null $shared_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $decided_at
 * @property Carbon|null $due_on
 */
#[Fillable([
    'mobility_case_id', 'type', 'title', 'status', 'provided_by', 'document_id', 'employer_can_view', 'reason_code', 'note',
    'requested_by', 'decided_by', 'shared_at', 'revoked_at', 'decided_at', 'due_on',
])]
#[UseFactory(CaseDocumentFactory::class)]
class CaseDocument extends Model
{
    /** @use HasFactory<CaseDocumentFactory> */
    use HasFactory, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => CaseDocumentStatus::class,
            'provided_by' => DocumentProvider::class,
            'employer_can_view' => 'boolean',
            'shared_at' => 'datetime',
            'revoked_at' => 'datetime',
            'decided_at' => 'datetime',
            'due_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<MobilityCase, $this>
     */
    public function mobilityCase(): BelongsTo
    {
        return $this->belongsTo(MobilityCase::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function fromWorker(): bool
    {
        return $this->provided_by === DocumentProvider::Worker;
    }

    /**
     * The file is visible to the case's organizations only while shared and not revoked.
     */
    public function isShared(): bool
    {
        return $this->document_id !== null && $this->shared_at !== null && $this->revoked_at === null;
    }
}
