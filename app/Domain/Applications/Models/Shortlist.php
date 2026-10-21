<?php

namespace App\Domain\Applications\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use Database\Factories\ShortlistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named list of workers an organization is considering. Being on a list never gives the
 * organization ownership of, or more access to, the worker's profile (spec §8).
 */
#[Fillable(['organization_id', 'name', 'job_id', 'created_by'])]
#[UseFactory(ShortlistFactory::class)]
class Shortlist extends Model
{
    /** @use HasFactory<ShortlistFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The job the list was made for, if any.
     *
     * @return BelongsTo<JobPosting, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ShortlistEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ShortlistEntry::class)->latest();
    }
}
