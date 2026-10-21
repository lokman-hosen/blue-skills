<?php

namespace App\Domain\Messaging\Support;

use App\Domain\Identity\Models\User;
use Carbon\CarbonImmutable;

/**
 * No WhatsApp or SMS to workers at night in their own time zone (D-067 plan, 5d).
 */
final class QuietHours
{
    /**
     * When a held message may go out, or null to send now.
     */
    public static function releaseAt(User $user, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        if (! $user->isWorker()) {
            return null;
        }

        $from = (int) config('blueskills.notifications.quiet_from', 22);
        $until = (int) config('blueskills.notifications.quiet_until', 7);
        $local = ($now ?? CarbonImmutable::now())->setTimezone($user->timezone ?: 'UTC');

        if ($local->hour >= $until && $local->hour < $from) {
            return null;
        }

        $release = $local->setTime($until, 0);

        return ($local->hour >= $from ? $release->addDay() : $release)->utc();
    }
}
