<?php

namespace App\Domain\Applications\Models;

use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Offer terms sent to a worker. Every offer is kept so the application has its offer history
 * (spec §6). Pay starts from the job and cannot go below what the Truth Card promised.
 *
 * @property Carbon|null $start_on
 * @property Carbon|null $respond_by
 * @property Carbon|null $responded_at
 */
#[Fillable([
    'application_id', 'salary_amount_minor', 'salary_currency', 'salary_period', 'start_on', 'respond_by', 'note',
    'status', 'responded_at', 'sent_by',
])]
#[UseFactory(OfferFactory::class)]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salary_amount_minor' => 'integer',
            'salary_period' => SalaryPeriod::class,
            'start_on' => 'date',
            'respond_by' => 'date',
            'status' => OfferStatus::class,
            'responded_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
