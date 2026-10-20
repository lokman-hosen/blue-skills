<?php

namespace App\Domain\Worker\Models;

use App\Domain\Config\Models\Country;
use Database\Factories\WorkerExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A period of work. Either exact dates or only the number of years the worker gave
 * ("five years in Bangladesh"), never both guessed from each other.
 */
#[Fillable(['worker_profile_id', 'country_code', 'employer_name', 'role', 'years', 'started_on', 'ended_on', 'description'])]
#[UseFactory(WorkerExperienceFactory::class)]
class WorkerExperience extends Model
{
    /** @use HasFactory<WorkerExperienceFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'years' => 'integer',
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    /**
     * Whole years worked: as stated, or rounded from the dates (an open period runs to today).
     */
    public function yearsWorked(): ?int
    {
        if ($this->years !== null) {
            return $this->years;
        }

        if ($this->started_on === null) {
            return null;
        }

        // Nearest whole year: Jan 2016 – Dec 2020 is five years, not four.
        return (int) round($this->started_on->diffInYears($this->ended_on ?? now()));
    }

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
