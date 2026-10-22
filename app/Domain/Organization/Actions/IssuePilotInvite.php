<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\PilotInvite;
use Carbon\CarbonInterface;

/**
 * Issues a pilot invite code for a selected partner (D-108). Staff hand it over themselves;
 * nothing is sent from here.
 */
class IssuePilotInvite
{
    public function handle(string $partnerName, ?OrganizationType $type, int $maxUses, ?CarbonInterface $expiresOn, User $actor): PilotInvite
    {
        $invite = PilotInvite::query()->create([
            'code' => PilotInvite::newCode(),
            'partner_name' => $partnerName,
            'organization_type' => $type,
            'max_uses' => $maxUses,
            'expires_at' => $expiresOn?->endOfDay(),
            'created_by' => $actor->id,
        ]);

        activity('organization')->performedOn($invite)->causedBy($actor)
            ->withProperties(['partner' => $partnerName, 'type' => $type?->value, 'max_uses' => $maxUses])
            ->log('pilot_invite.issued');

        return $invite;
    }
}
