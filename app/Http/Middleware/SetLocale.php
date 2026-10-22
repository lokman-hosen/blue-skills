<?php

namespace App\Http\Middleware;

use App\Domain\Config\Support\LocaleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the interface locale: ?lang= (remembered in the session), then the session, then
 * the signed-in user's preference, then the browser's Accept-Language, then English.
 */
class SetLocale
{
    public const SESSION_KEY = 'locale';

    public function __construct(private readonly LocaleRegistry $locales) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $query = $request->query('lang');

        if (is_string($query) && $this->locales->supports($query) && $request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $query);
        }

        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    /** The locale for a request; also used for error pages, which can render before this middleware runs. */
    public function resolve(Request $request): string
    {
        $candidates = [
            $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null,
            $request->user()?->locale,
            $request->getPreferredLanguage($this->locales->codes()),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $this->locales->supports($candidate)) {
                return $candidate;
            }
        }

        return $this->locales->fallback();
    }
}
