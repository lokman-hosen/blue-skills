<?php

use App\Http\Middleware\BlockSuspendedOrganizationChanges;
use App\Http\Middleware\EnsureOrganizationVerified;
use App\Http\Middleware\EnsureStaffTwoFactor;
use App\Http\Middleware\EnsureUserType;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\ErrorPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'user.type' => EnsureUserType::class,
            'organization.verified' => EnsureOrganizationVerified::class,
            'staff.2fa' => EnsureStaffTwoFactor::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            'throttle:writes',
            HandleAppearance::class,
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            BlockSuspendedOrganizationChanges::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Error pages in the site's design (D-107). Local debug keeps Laravel's 500 page with the trace.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            if ($request->expectsJson()) {
                return $response;
            }

            if ($status === 419) {
                if ($request->hasSession()) {
                    Inertia::flash('toast', ['type' => 'warning', 'message' => __('errors.page_expired')]);
                }

                return back();
            }

            $inertiaServerError = $status >= 500 && $request->header('X-Inertia') && ! config('app.debug');

            return in_array($status, ErrorPage::STATUSES, true) || $inertiaServerError
                ? ErrorPage::render($request, $status)
                : $response;
        });
    })->create();
