<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;

/**
 * Who can find the Skill Passport (D-039). Being discoverable by employers and agencies is a
 * consent: granting it opens a `discovery` consent, narrowing it revokes that consent. Phone
 * numbers and passport scans stay out of search either way (product rule 6).
 */
class UpdateProfileVisibility
{
    public function handle(WorkerProfile $profile, ProfileVisibility $visibility): void
    {
        if ($profile->visibility === $visibility) {
            return;
        }

        DB::transaction(function () use ($profile, $visibility): void {
            $profile->consents()->active()->where('purpose', ConsentPurpose::Discovery)->update(['revoked_at' => now()]);

            if ($visibility === ProfileVisibility::EmployersAndAgencies) {
                $profile->consents()->create([
                    'purpose' => ConsentPurpose::Discovery,
                    'scope' => ['visibility' => $visibility->value],
                    'granted_at' => now(),
                ]);
            }

            // The model logs the before/after value (LogsStatusChanges).
            $profile->update(['visibility' => $visibility]);
        });
    }
}
