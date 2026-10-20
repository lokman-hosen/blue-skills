<?php

namespace App\Domain\Applications\Models;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use Database\Factories\ApplicationConsentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-item sharing choice made when applying (passport scan and phone are off by default).
 */
#[Fillable(['application_id', 'item', 'granted'])]
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
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
