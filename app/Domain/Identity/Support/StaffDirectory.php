<?php

namespace App\Domain\Identity\Support;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

class StaffDirectory
{
    public function roleOf(User $user): ?StaffRole
    {
        $name = PlatformTeam::run(fn () => $user->unsetRelation('roles')->getRoleNames()->first());
        $user->unsetRelation('roles');

        return is_string($name) ? StaffRole::tryFrom($name) : null;
    }

    public function superuserCount(): int
    {
        return DB::table(config('permission.table_names.model_has_roles'))
            ->join(config('permission.table_names.roles'), 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', 'users.id', '=', 'model_has_roles.'.config('permission.column_names.model_morph_key'))
            ->where('model_has_roles.'.config('permission.column_names.team_foreign_key'), PlatformTeam::ID)
            ->where('roles.name', StaffRole::Superuser->value)
            ->whereNull('users.deleted_at')
            ->count();
    }
}
