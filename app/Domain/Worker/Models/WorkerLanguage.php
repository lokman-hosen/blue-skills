<?php

namespace App\Domain\Worker\Models;

use App\Domain\Config\Models\Language;
use App\Domain\Worker\Enums\LanguageProficiency;
use Database\Factories\WorkerLanguageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['worker_profile_id', 'language_code', 'proficiency'])]
#[UseFactory(WorkerLanguageFactory::class)]
class WorkerLanguage extends Model
{
    /** @use HasFactory<WorkerLanguageFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'proficiency' => LanguageProficiency::class,
        ];
    }

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_code', 'code');
    }
}
