<?php

namespace App\Domain\Applications\Models;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Verification\Models\Document;
use Database\Factories\ApplicationConsentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Per-item sharing choice made when applying (passport scan and phone are off by default).
 * The worker can revoke an item later; revoking sets revoked_at and keeps the row (product rule 6).
 *
 * @property Carbon|null $granted_at
 * @property Carbon|null $revoked_at
 */
#[Fillable(['application_id', 'item', 'granted', 'document_id', 'granted_at', 'revoked_at'])]
#[UseFactory(ApplicationConsentFactory::class)]
class ApplicationConsent extends Model
{
    /** @use HasFactory<ApplicationConsentFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item' => ApplicationConsentItem::class,
            'granted' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function isActive(): bool
    {
        return $this->granted && $this->revoked_at === null;
    }

    /**
     * Items currently shared: granted and not revoked.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('granted', true)->whereNull('revoked_at');
    }
}
