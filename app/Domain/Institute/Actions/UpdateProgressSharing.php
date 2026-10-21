<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Institute\Models\CohortMember;

/**
 * The graduate turns sharing their applications and placement with the institute on or off
 * (D-082). Off takes effect at once.
 */
class UpdateProgressSharing
{
    public function handle(CohortMember $member, bool $sharesProgress): void
    {
        $member->update(['shares_progress' => $sharesProgress]);
    }
}
