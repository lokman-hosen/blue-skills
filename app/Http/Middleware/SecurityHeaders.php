<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers on every response (D-105): a Content Security Policy with a
 * per-request script nonce (Vite tags and the inline appearance script carry it), no framing,
 * no MIME sniffing, a strict referrer and a Permissions-Policy that only lets this site use the
 * camera and microphone (SkillShow, Voice-to-CV). HSTS is sent over HTTPS only.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $headers = $response->headers;
        $headers->set(config('blueskills.security.csp_enforce') ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only', $this->policy($nonce));
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure() && config('blueskills.security.hsts_max_age') > 0) {
            $headers->set('Strict-Transport-Security', 'max-age='.config('blueskills.security.hsts_max_age').'; includeSubDomains');
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        $hot = $this->viteDevServer();
        $media = $this->sources('blueskills.security.media_sources');

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", ...$hot],
            // React and Radix set style attributes at runtime, so inline styles stay allowed.
            'style-src' => ["'self'", "'unsafe-inline'", ...$hot],
            'img-src' => ["'self'", 'data:', 'blob:', ...$media],
            'media-src' => ["'self'", 'blob:', ...$media],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'", ...$this->reverb(), ...$hot, ...$this->websocket($hot), ...$this->sources('blueskills.security.connect_sources')],
            'worker-src' => ["'self'", 'blob:'],
            'manifest-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        $policy = collect($directives)
            ->map(fn (array $sources, string $directive): string => $directive.' '.implode(' ', array_unique($sources)))
            ->values();

        if ($uri = config('blueskills.security.csp_report_uri')) {
            $policy->push("report-uri {$uri}");
        }

        return $policy->implode('; ');
    }

    /** @return list<string> */
    private function sources(string $key): array
    {
        return array_values(array_filter(explode(' ', (string) config($key))));
    }

    /**
     * The Reverb socket the browser opens for live updates (Module 5).
     *
     * @return list<string>
     */
    private function reverb(): array
    {
        $options = config('blueskills.security.reverb');

        if (config('broadcasting.default') !== 'reverb' || ! is_array($options) || empty($options['host'])) {
            return [];
        }

        $secure = ($options['scheme'] ?? 'https') === 'https';
        $port = (int) ($options['port'] ?? ($secure ? 443 : 80));
        $host = $options['host'].(in_array($port, [80, 443], true) ? '' : ":{$port}");

        return [($secure ? 'wss://' : 'ws://').$host];
    }

    /**
     * The Vite dev server while `npm run dev` runs (public/hot exists), so local pages load.
     *
     * @return list<string>
     */
    private function viteDevServer(): array
    {
        if (! Vite::isRunningHot()) {
            return [];
        }

        return [rtrim((string) file_get_contents(Vite::hotFile()), "/ \n")];
    }

    /**
     * @param  list<string>  $hot
     * @return list<string>
     */
    private function websocket(array $hot): array
    {
        return array_map(fn (string $url): string => Str::replaceFirst('http', 'ws', $url), $hot);
    }
}
