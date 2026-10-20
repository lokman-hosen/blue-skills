<?php

namespace App\Domain\Identity\Support;

/**
 * spatie-permission teams mode needs a team id on every role assignment. Staff roles are
 * global, so they are assigned under this reserved id, which is never a real organization.
 */
final class PlatformTeam
{
    public const ID = '00000000000000000000000000';

    /**
     * Run a callback with the permission team set to the platform, then restore the previous team.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        return TeamContext::run(self::ID, $callback);
    }
}
