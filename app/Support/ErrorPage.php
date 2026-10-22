<?php

namespace App\Support;

use App\Domain\Config\Data\LocaleData;
use App\Domain\Config\Support\LocaleRegistry;
use App\Domain\Config\Support\Translations;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Friendly error pages in the site's own design (D-107). Errors raised before the Inertia
 * middleware ran (a missing record in route binding, a rate limit) get the locale and shared
 * props here. If even that fails, as when the database is down, the page still renders with
 * just its translations.
 */
final class ErrorPage
{
    /** Statuses that show the Inertia page; 500 does too for Inertia visits (full loads get errors/500). */
    public const STATUSES = [403, 404, 429];

    public static function render(Request $request, int $status): Response
    {
        if (Inertia::getShared('translations') === null) {
            self::share($request);
        }

        return Inertia::render('public/error', ['status' => $status])
            ->toResponse($request)
            ->setStatusCode($status);
    }

    private static function share(Request $request): void
    {
        try {
            app()->setLocale(app(SetLocale::class)->resolve($request));
            Inertia::share(app(HandleInertiaRequests::class)->share($request));
        } catch (Throwable) {
            $locale = app()->getLocale();
            $locales = app(LocaleRegistry::class);

            Inertia::share([
                'auth' => ['user' => null],
                'locale' => new LocaleData(code: $locale, dir: $locales->isRtl($locale) ? 'rtl' : 'ltr', translationPending: false),
                'translations' => app(Translations::class)->for($locale),
            ]);
        }
    }
}
