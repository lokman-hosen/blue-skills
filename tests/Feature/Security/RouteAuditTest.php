<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Inertia\DevTools\DevTools;

/*
| Guards against a new route shipping without sign-in or authorization (D-105). A route that is
| deliberately public, or only ever touches the signed-in user's own records, is listed here
| with the reason; anything else must call a policy or gate.
*/

/** Routes anyone may open without signing in. */
const PUBLIC_ROUTES = [
    'home', 'jobs.index', 'jobs.show',
    'campaign-link.show', 'cohort-invite.show', 'locale.update',
    // Worker phone sign-in and sign-up (guest only).
    'worker.join', 'worker.phone', 'worker.phone.store', 'worker.verify', 'worker.verify.store',
    'worker.resend', 'worker.details', 'worker.details.store',
    // Invitation links carry a secret token.
    'invitations.show', 'staff-invitations.show', 'staff-invitations.store',
];

/** Signed-in routes that only read or change the user's own records, so no policy applies. */
const OWN_DATA_ROUTES = [
    'dashboard', 'notifications.index', 'notifications.preferences', 'notifications.preferences.update',
    'notifications.read-all', 'notifications.open', 'profile.edit', 'profile.update',
    'security.edit', 'user-password.update', 'messages.index', 'invitations.index',
];

/** @return list<RoutingRoute> */
function appRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\'))
        ->values()
        ->all();
}

function sourceOf(ReflectionMethod $method): string
{
    $lines = file((string) $method->getFileName()) ?: [];

    return implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
}

/** The action's source plus the controller methods it calls on `$this` (one level). */
function actionSource(ReflectionMethod $action): string
{
    $source = sourceOf($action);
    $class = $action->getDeclaringClass();

    preg_match_all('/\$this->(\w+)\(/', $source, $calls);

    foreach (array_unique($calls[1]) as $name) {
        if ($class->hasMethod($name)) {
            $source .= sourceOf($class->getMethod($name));
        }
    }

    return $source;
}

function authorizes(RoutingRoute $route): bool
{
    $action = $route->getActionName();
    [$class, $method] = str_contains($action, '@') ? explode('@', $action) : [$action, '__invoke'];
    $reflection = new ReflectionMethod($class, $method);

    if (str_contains(implode(' ', $route->gatherMiddleware()), 'can:')
        || preg_match('/authorize\(|Gate::|->can\(|->cannot\(|abort_(unless|if)\(/', actionSource($reflection))) {
        return true;
    }

    foreach ($reflection->getParameters() as $parameter) {
        $type = $parameter->getType();
        $name = $type instanceof ReflectionNamedType ? $type->getName() : null;

        if ($name !== null && is_subclass_of($name, FormRequest::class) && method_exists($name, 'authorize')
            && ! str_contains(sourceOf(new ReflectionMethod($name, 'authorize')), 'return true;')) {
            return true;
        }
    }

    return false;
}

it('requires sign-in on every route that is not deliberately public', function () {
    $open = collect(appRoutes())
        ->reject(fn (RoutingRoute $route): bool => in_array('auth', $route->gatherMiddleware(), true))
        ->map(fn (RoutingRoute $route): string => $route->getName() ?? $route->uri())
        ->reject(fn (string $name): bool => in_array($name, PUBLIC_ROUTES, true))
        ->values()
        ->all();

    expect($open)->toBe([]);
});

it('authorizes every signed-in controller action', function () {
    $unchecked = collect(appRoutes())
        ->reject(fn (RoutingRoute $route): bool => in_array($route->getName(), [...PUBLIC_ROUTES, ...OWN_DATA_ROUTES], true))
        ->reject(fn (RoutingRoute $route): bool => authorizes($route))
        ->map(fn (RoutingRoute $route): string => $route->methods()[0].' '.$route->uri().' → '.$route->getActionName())
        ->values()
        ->all();

    expect($unchecked)->toBe([]);
});

it('keeps admin routes behind staff sign-in with two-factor', function () {
    collect(appRoutes())
        ->filter(fn (RoutingRoute $route): bool => str_starts_with($route->uri(), 'admin/'))
        ->each(fn (RoutingRoute $route) => expect($route->gatherMiddleware())->toContain('auth', 'user.type:staff', 'staff.2fa'));
});

it('limits writes on every app route', function () {
    expect(app(Kernel::class)->getMiddlewareGroups()['web'])->toContain('throttle:writes');

    collect(appRoutes())->each(fn (RoutingRoute $route) => expect($route->gatherMiddleware())->toContain('web'));
});

it('never records Inertia devtools outside a local machine', function () {
    config(['inertia.devtools.enabled' => null]);

    app()->detectEnvironment(fn () => 'production');
    expect(DevTools::enabled())->toBeFalse();

    app()->detectEnvironment(fn () => 'testing');
});
