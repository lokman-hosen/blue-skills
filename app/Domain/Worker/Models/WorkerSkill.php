<?php

namespace App\Domain\Worker\Models;

use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Worker\Enums\SkillSource;
use Database\Factories\WorkerSkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['worker_profile_id', 'skill_id', 'source', 'confirmed_at'])]
#[UseFactory(WorkerSkillFactory::class)]
class WorkerSkill extends Pivot
{
    /** @use HasFactory<WorkerSkillFactory> */
    use HasFactory, HasUlids;

    protected $table = 'worker_skills';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => SkillSource::class,
            'confirmed_at' => 'datetime',
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
     * @return BelongsTo<Skill, $this>
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
