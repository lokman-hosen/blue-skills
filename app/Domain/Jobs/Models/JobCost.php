<?php

namespace App\Domain\Jobs\Models;

use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\JobCostCategory;
use Database\Factories\JobCostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A job-related cost and who pays it. Part of the job fee ledger, never platform billing
 * (product rule 13).
 */
#[Fillable(['job_id', 'category', 'description', 'amount_minor', 'currency', 'payer'])]
#[UseFactory(JobCostFactory::class)]
class JobCost extends Model
{
    /** @use HasFactory<JobCostFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => JobCostCategory::class,
            'amount_minor' => 'integer',
            'payer' => CostPayer::class,
        ];
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }
}
