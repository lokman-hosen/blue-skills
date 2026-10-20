<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Enums\UserType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lands each user type in its own workspace. The screens are placeholders until their
 * modules ship (worker home: Module 2, organization dashboard: Module 3, admin: Module 8).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return match ($request->user()?->type) {
            UserType::Worker => Inertia::render('worker/home'),
            UserType::Staff => Inertia::render('admin/overview'),
            default => Inertia::render('org/dashboard'),
        };
    }
}
