<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Enums\UserType;
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
            // Organization users without a workspace finish registration first.
            default => $user !== null && $currentOrganization->resolve($user, $request->session()) === null
                ? to_route('organizations.create')
                : Inertia::render('org/dashboard'),
        };
    }
}
