<?php

namespace App\Domain\Identity\Data;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\StaffInvitation;
use Spatie\LaravelData\Data;

class StaffInvitationData extends Data
{
    public function __construct(
        public string $id,
        public string $email,
        public StaffRole $role,
        public string $expiresAt,
        public ?string $invitedBy,
    ) {}

    public static function fromInvitation(StaffInvitation $invitation): self
    {
        return new self($invitation->id, $invitation->email, $invitation->role, $invitation->expires_at->toIso8601String(), $invitation->inviter?->name);
    }
}
