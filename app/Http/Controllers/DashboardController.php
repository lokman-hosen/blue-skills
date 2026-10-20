<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Enums\UserType;
use App\Domain\Organization\Models\OrganizationInvitation;
use App\Domain\Organization\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lands each user type in its own workspace. The screens are placeholders until their
 * modules ship (worker home: Module 2, organization dashboard: Module 3, admin: Module 8).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, CurrentOrganization $currentOrganization): Response|RedirectResponse
    {
        $user = $request->user();

        return match ($user?->type) {
            UserType::Worker => Inertia::render('worker/home'),
            UserType::Staff => Inertia::render('admin/overview'),
            // Organization users without a workspace accept an invitation or register one first.
            default => match (true) {
                $user === null || $currentOrganization->resolve($user, $request->session()) !== null => Inertia::render('org/dashboard'),
                $this->hasPendingInvitation((string) $user->email) => to_route('invitations.index'),
                default => to_route('organizations.create'),
            },
        };
    }

    private function hasPendingInvitation(string $email): bool
    {
        return OrganizationInvitation::query()->pendingFor($email)->exists();
    }
}
