<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\RejectionReason;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Rejected;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Rejects many applicants at once with one reason the workers see (bulk screening, spec §6).
 * Applicants who can no longer be rejected (accepted, withdrawn, already rejected) are skipped.
 */
class RejectApplications
{
    public function __construct(private RejectApplication $reject) {}

    /**
     * @param  Collection<int, Application>  $applications
     * @return array{rejected: int, skipped: int}
     */
    public function handle(Collection $applications, RejectionReason $reason, ?string $note, User $actor): array
    {
        $rejected = 0;

        foreach ($applications as $application) {
            if (! $application->status->canTransitionTo(Rejected::class)) {
                continue;
            }

            $this->reject->handle($application, $reason, $note, $actor);
            $rejected++;
        }

        if ($rejected === 0) {
            throw ValidationException::withMessages(['reason' => __('applications.stage.none_rejected')]);
        }

        return ['rejected' => $rejected, 'skipped' => $applications->count() - $rejected];
    }
}
