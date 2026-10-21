<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Models\NotificationPreference;
use App\Domain\Messaging\Support\Preferences;
use Illuminate\Support\Facades\DB;

/**
 * Saves which outside channels a person wants per event. In-app notifications stay on.
 */
class UpdateNotificationPreferences
{
    /**
     * @param  array<string, array<string, bool>>  $choices  event => channel => enabled
     */
    public function handle(User $user, array $choices): void
    {
        DB::transaction(function () use ($user, $choices): void {
            foreach (Preferences::eventsFor($user) as $event) {
                foreach ($event->channels() as $channel) {
                    NotificationPreference::query()->updateOrCreate(
                        ['user_id' => $user->id, 'event' => $event, 'channel' => $channel],
                        ['enabled' => (bool) ($choices[$event->value][$channel->value] ?? false)],
                    );
                }
            }

            activity('notifications')->performedOn($user)->causedBy($user)
                ->withProperties(['preferences' => $choices])
                ->log('notification_preferences.updated');
        });
    }
}
