<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Validation\ValidationException;

/**
 * The worker hides or shows an institute's attestation or assessment on their Skill Passport
 * (D-082). They can't change it; hidden items are left out of everything organizations see.
 */
class SetEvidenceHidden
{
    public function handle(WorkerProfile $profile, EvidenceItem $item, bool $hidden): void
    {
        if ($item->worker_profile_id !== $profile->id || ! $item->isIssuedByInstitute()) {
            throw ValidationException::withMessages(['evidence' => __('evidence.not_hideable')]);
        }

        $item->update(['hidden_at' => $hidden ? now() : null]);

        activity('worker')->performedOn($item)->causedBy($profile->user)
            ->withProperties(['hidden' => $hidden])
            ->log($hidden ? 'evidence.hidden' : 'evidence.shown');
    }
}
