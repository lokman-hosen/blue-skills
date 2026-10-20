<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff must confirm two-factor authentication before any staff screen (D-035).
 * Does nothing for workers and organization users.
 */
class EnsureStaffTwoFactor
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isStaff() && config('blueskills.enforce_staff_two_factor') && $user->two_factor_confirmed_at === null) {
            if ($request->expectsJson()) {
                abort(403, __('staff.two_factor_required'));
            }

            Inertia::flash('toast', ['type' => 'warning', 'message' => __('staff.two_factor_required')]);

            return to_route('security.edit');
        }

        return $next($request);
    }
}
