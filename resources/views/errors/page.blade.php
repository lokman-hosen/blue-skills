{{--
    Standalone error page for 500 and 503 (D-107): no database, session or JavaScript, because
    those may be what failed. Colours mirror the --bs-* tokens in resources/css/app.css.
--}}
@php
    $locale = app()->getLocale();
    try {
        $rtl = app(\App\Domain\Config\Support\LocaleRegistry::class)->isRtl($locale);
    } catch (\Throwable) {
        $rtl = false;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#153e9e">
    <meta name="robots" content="noindex">
    <title>{{ __("errors.{$status}.title") }} · BLUESKILLS</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root { --bg: rgb(243 245 250); --surface: rgb(255 255 255); --ink: rgb(14 22 46); --muted: rgb(86 97 124); --brand: rgb(21 62 158); --on-brand: rgb(255 255 255); --line: rgb(214 220 233); }
        @media (prefers-color-scheme: dark) {
            :root { --bg: rgb(9 13 26); --surface: rgb(16 22 40); --ink: rgb(230 235 247); --muted: rgb(150 161 189); --brand: rgb(122 160 255); --on-brand: rgb(8 14 34); --line: rgb(40 50 80); }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: var(--bg); color: var(--ink); font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Bengali", sans-serif; }
        main { width: 100%; max-width: 440px; background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 28px 24px; text-align: center; }
        .mark { display: inline-grid; place-items: center; width: 48px; height: 48px; border-radius: 12px; background: var(--brand); color: var(--on-brand); }
        .code { margin: 16px 0 0; font: 600 12px/1 ui-monospace, monospace; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        h1 { font-size: 1.25rem; margin: 8px 0 4px; }
        p { margin: 0; color: var(--muted); }
        a { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; margin-top: 20px; padding: 0 20px; border-radius: 10px; background: var(--brand); color: var(--on-brand); font-weight: 600; text-decoration: none; }
        a:focus-visible { outline: 3px solid var(--ink); outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <span class="mark" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 10h2"/><path d="M16 14h2"/><path d="M6.17 15a3 3 0 0 1 5.66 0"/><circle cx="9" cy="11" r="2"/><rect x="2" y="5" width="20" height="14" rx="2"/></svg>
        </span>
        <p class="code">{{ __('errors.code', ['code' => $status]) }}</p>
        <h1>{{ __("errors.{$status}.title") }}</h1>
        <p>{{ __("errors.{$status}.body") }}</p>
        <a href="">{{ __('errors.try_again') }}</a>
    </main>
</body>
</html>
