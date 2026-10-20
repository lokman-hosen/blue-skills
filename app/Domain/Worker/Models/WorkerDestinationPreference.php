<?php

namespace App\Domain\Worker\Models;

use App\Domain\Config\Models\Country;
use Database\Factories\WorkerDestinationPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['worker_profile_id', 'country_code', 'priority'])]
#[UseFactory(WorkerDestinationPreferenceFactory::class)]
class WorkerDestinationPreference extends Model
{
    /** @use HasFactory<WorkerDestinationPreferenceFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }
}
