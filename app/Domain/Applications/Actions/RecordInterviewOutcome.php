<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\InterviewOutcome;
use App\Domain\Applications\Models\Interview;
use App\Domain\Identity\Models\User;

/**
 * Records how an interview went. Deciding what happens next (select, assess or reject) is a
 * separate, explicit stage move by a person (product rule 14).
 */
class RecordInterviewOutcome
{
    public function handle(Interview $interview, InterviewOutcome $outcome, ?string $note, User $actor): void
    {
        $previous = $interview->outcome?->value;
        $interview->update(['outcome' => $outcome, 'outcome_note' => $note]);

        activity('applications')->performedOn($interview->application)->causedBy($actor)
            ->withProperties(['interview' => $interview->id, 'old' => ['outcome' => $previous], 'attributes' => ['outcome' => $outcome->value]])
            ->log('interview.outcome_recorded');
    }
}
