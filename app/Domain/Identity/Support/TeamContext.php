<?php

namespace App\Domain\Identity\Support;

use Spatie\Permission\PermissionRegistrar;

final class TeamContext
{
    /**
     * Run a callback inside a permission team (an organization id or PlatformTeam::ID).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function run(string $teamId, callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($teamId);

        try {
            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}
