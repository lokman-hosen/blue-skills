<?php

namespace App\Domain\Identity\Models;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Support\PlatformTeam;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationMember;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property UserType $type
 * @property string $name
 * @property PhoneNumber|null $phone
 * @property string|null $email
 * @property Carbon|null $phone_verified_at
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string $locale
 * @property string $timezone
 * @property string $data_region
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['type', 'name', 'phone', 'email', 'password', 'locale', 'timezone', 'data_region'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUlids, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => UserType::class,
            'phone' => E164PhoneNumberCast::class,
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<WorkerProfile, $this>
     */
    public function workerProfile(): HasOne
    {
        return $this->hasOne(WorkerProfile::class);
    }

    /**
     * @return HasMany<OrganizationMember, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    /**
     * @return BelongsToMany<Organization, $this, OrganizationMember>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members')
            ->using(OrganizationMember::class)
            ->withPivot(['id', 'title', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_user_id');
    }

    /**
     * Workers sign in by phone OTP and may have no email, so for them a verified phone
     * satisfies the `verified` middleware.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->isWorker() ? $this->phone_verified_at !== null : parent::hasVerifiedEmail();
    }

    public function isWorker(): bool
    {
        return $this->type === UserType::Worker;
    }

    public function isStaff(): bool
    {
        return $this->type === UserType::Staff;
    }

    public function assignStaffRole(StaffRole $role): static
    {
        PlatformTeam::run(fn () => $this->unsetRelation('roles')->assignRole($role->value));

        return $this->unsetRelation('roles');
    }

    public function hasStaffRole(StaffRole $role): bool
    {
        if (! $this->isStaff()) {
            return false;
        }

        return PlatformTeam::run(fn () => $this->unsetRelation('roles')->hasRole($role->value));
    }

    public function hasStaffPermission(string $permission): bool
    {
        if (! $this->isStaff()) {
            return false;
        }

        return PlatformTeam::run(fn () => $this->unsetRelation('roles')->unsetRelation('permissions')->hasPermissionTo($permission));
    }
}
