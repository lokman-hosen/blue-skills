<?php

namespace App\Http\Middleware;

use App\Domain\Identity\Enums\UserType;
use App\Domain\Organization\States\Verification\Suspended;
use App\Domain\Organization\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * A suspended organization's members can read and appeal but change nothing in its workspace
 * (D-088): no posting, publishing, job changes, messages, hiring steps or team changes.
 */
class BlockSuspendedOrganizationChanges
{
    /** Workspace routes a suspended organization can't change. */
    private const PREFIXES = ['org.', 'team.', 'organizations.'];

    public function __construct(private readonly CurrentOrganization $currentOrganization) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = (string) $request->route()?->getName();

        $inWorkspace = Str::startsWith($route, self::PREFIXES);

        if ($user === null || $user->type !== UserType::Org || $request->isMethodSafe() || ! $inWorkspace) {
            return $next($request);
        }

        $organization = $this->currentOrganization->resolve($user, $request->session());

        if ($organization === null || ! $organization->verification_status->equals(Suspended::class)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, __('trust.suspended_read_only'));
        }

        Inertia::flash('toast', ['type' => 'error', 'message' => __('trust.suspended_read_only')]);

        return back();
    }
}
