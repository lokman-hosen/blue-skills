<?php

namespace App\Domain\Identity\Data;

use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PlatformTeam;
use Spatie\LaravelData\Data;

/**
 * The signed-in user as shared with every Inertia page. Never includes phone numbers.
 */
class AuthUserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $emailVerifiedAt,
        public UserType $type,
        public string $locale,
        public bool $twoFactorEnabled,
        /** @var list<string> Staff permissions (empty for workers and organization users). */
        public array $staffPermissions,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            emailVerifiedAt: $user->email_verified_at?->toIso8601String(),
            type: $user->type,
            locale: $user->locale,
            twoFactorEnabled: $user->two_factor_confirmed_at !== null,
            staffPermissions: $user->isStaff()
                ? PlatformTeam::run(fn () => $user->unsetRelation('roles')->unsetRelation('permissions')->getAllPermissions()->pluck('name')->values()->all())
                : [],
        );
    }
}
