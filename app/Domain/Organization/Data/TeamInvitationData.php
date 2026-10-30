<?php

namespace App\Domain\Organization\Data;

use App\Domain\Organization\Models\OrganizationInvitation;
use Spatie\LaravelData\Data;

class TeamInvitationData extends Data
{
    public function __construct(
        public string $id,
        public ?string $email,
        /** Role name; label via `role.{name}`. */
        public string $role,
        public string $expiresAt,
        public ?string $invitedBy,
        public OrganizationSummaryData $organization,
    ) {}

    public static function fromInvitation(OrganizationInvitation $invitation): self
    {
        return new self(
            id: $invitation->id,
            email: $invitation->email,
            role: $invitation->role,
            expiresAt: $invitation->expires_at->toIso8601String(),
            invitedBy: $invitation->inviter?->name,
            organization: OrganizationSummaryData::fromOrganization($invitation->organization),
        );
    }
}
