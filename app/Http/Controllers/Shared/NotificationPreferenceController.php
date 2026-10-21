<?php

namespace App\Http\Controllers\Shared;

use App\Domain\Messaging\Actions\UpdateNotificationPreferences;
use App\Domain\Messaging\Data\PreferenceRowData;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Preferences;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Which alerts also come by WhatsApp, SMS or email (D-068).
 */
class NotificationPreferenceController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('notifications/preferences', [
            'rows' => array_map(fn (NotificationEvent $event) => PreferenceRowData::forUser($user, $event), Preferences::eventsFor($user)),
            'quietHours' => $user->isWorker() ? [
                'from' => (int) config('blueskills.notifications.quiet_from'),
                'until' => (int) config('blueskills.notifications.quiet_until'),
            ] : null,
        ]);
    }

    public function update(Request $request, UpdateNotificationPreferences $update): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => ['present', 'array'],
            'preferences.*' => ['array'],
            'preferences.*.*' => ['boolean'],
        ]);

        /** @var array<string, array<string, bool>> $choices */
        $choices = $validated['preferences'];
        $update->handle($request->user(), $choices);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('notifications.preferences_saved')]);

        return to_route('notifications.preferences');
    }
}
