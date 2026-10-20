<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to one or more user types: `user.type:org` or `user.type:org,staff`.
 */
class EnsureUserType
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        abort_unless(in_array($request->user()?->type->value, $types, true), 403);

        return $next($request);
    }
}
