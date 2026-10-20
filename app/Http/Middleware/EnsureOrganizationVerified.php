<?php

namespace App\Http\Middleware;

use App\Domain\Organization\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Publishing jobs and searching talent need a verified organization (D-036). Module 3 applies
 * this to those routes.
 */
class EnsureOrganizationVerified
{
    public function __construct(private readonly CurrentOrganization $currentOrganization) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $organization = $user !== null ? $this->currentOrganization->resolve($user, $request->session()) : null;

        if ($organization === null || ! $organization->isVerified()) {
            if ($request->expectsJson()) {
                abort(403, __('organization.verification_required'));
            }

            Inertia::flash('toast', ['type' => 'error', 'message' => __('organization.verification_required')]);

            return to_route('dashboard');
        }

        return $next($request);
    }
}
