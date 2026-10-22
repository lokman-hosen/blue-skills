<?php

namespace App\Domain\Organization\Data;

use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\PilotInvite;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * A pilot invite code on the staff page, with the organizations that registered with it.
 */
class PilotInviteData extends Data
{
    /**
     * @param  list<string>  $organizations
     */
    public function __construct(
        public string $id,
        public string $code,
        public string $partnerName,
        public ?OrganizationType $organizationType,
        public int $maxUses,
        public int $usedCount,
        public ?string $expiresAt,
        public StatusData $status,
        public array $organizations,
        public ?string $createdBy,
        public string $createdAt,
    ) {}

    public static function fromInvite(PilotInvite $invite): self
    {
        $state = $invite->state();

        return new self(
            id: $invite->id,
            code: $invite->code,
            partnerName: $invite->partner_name,
            organizationType: $invite->organization_type,
            maxUses: $invite->max_uses,
            usedCount: $invite->used_count,
            expiresAt: $invite->expires_at?->toIso8601String(),
            status: new StatusData(
                value: $state,
                label: __("pilot.state.{$state}"),
                tone: match ($state) {
                    'active' => 'ok',
                    'used_up' => 'neutral',
                    default => 'bad',
                },
            ),
            organizations: $invite->organizations->map(fn (Organization $organization) => $organization->display_name)->values()->all(),
            createdBy: $invite->creator?->name,
            createdAt: $invite->created_at?->toIso8601String() ?? '',
        );
    }
}
