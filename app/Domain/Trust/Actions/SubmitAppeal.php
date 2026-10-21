<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Trust\Enums\AppealStatus;
use App\Domain\Trust\Models\Appeal;
use App\Domain\Trust\Models\TrustAction;
use Illuminate\Validation\ValidationException;

/**
 * The suspended party explains why the suspension should be lifted (spec §10). One appeal
 * waits at a time per suspension.
 */
class SubmitAppeal
{
    public function handle(TrustAction $suspension, User $appellant, string $statement): Appeal
    {
        if ($suspension->appeals()->where('status', AppealStatus::Submitted)->exists()) {
            throw ValidationException::withMessages(['statement' => __('trust.errors.appeal_pending')]);
        }

        $appeal = $suspension->appeals()->create([
            'appellant_user_id' => $appellant->id,
            'statement' => $statement,
            'status' => AppealStatus::Submitted,
        ]);

        activity('trust')->performedOn($suspension)->causedBy($appellant)
            ->withProperties(['appeal' => $appeal->id])
            ->log('appeal.submitted');

        return $appeal;
    }
}
