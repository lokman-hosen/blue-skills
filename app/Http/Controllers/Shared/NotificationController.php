<?php

namespace App\Http\Controllers\Shared;

use App\Domain\Messaging\Data\NotificationData;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-app notifications for every signed-in user (spec §9). Always on; other channels follow the
 * user's preferences.
 */
class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('notifications/index', [
            'notifications' => $user->notifications()->latest()->paginate(30)
                ->through(fn (DatabaseNotification $notification) => NotificationData::fromNotification($notification, $user)),
        ]);
    }

    /**
     * Marks one notification read and follows its link.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        /** @var DatabaseNotification $record */
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        /** @var string $url */
        $url = $record->data['url'] ?? '/';

        // Links are always app paths; never redirect off-site.
        return redirect(str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : '/');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
