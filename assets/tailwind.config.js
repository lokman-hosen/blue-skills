/* Shared Tailwind config for the Play CDN (load right after cdn.tailwindcss.com/3.4.17).
   The same object becomes tailwind.config.js in the Laravel app. Colours read CSS variables
   from assets/tokens.css, so light/dark switch without any dark: variants. */
(function () {
  const v = (name) => `rgb(var(--${name}) / <alpha-value>)`;
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          bg: v('bg'), surface: v('surface'), surface2: v('surface-2'),
          line: v('line'), linestrong: v('line-strong'),
          ink: v('ink'), muted: v('muted'),
          brand: v('brand'), brandsoft: v('brand-soft'), onbrand: v('on-brand'),
          stamp: v('stamp'), stampsoft: v('stamp-soft'),
          ok: v('ok'), oksoft: v('ok-soft'),
          warn: v('warn'), warnsoft: v('warn-soft'),
          bad: v('bad'), badsoft: v('bad-soft')
        },
        fontFamily: {
          display: ['"Bricolage Grotesque"', 'Figtree', 'system-ui', 'sans-serif'],
          sans: ['Figtree', '"Noto Sans Bengali"', '"Noto Sans Devanagari"', '"Noto Naskh Arabic"', 'system-ui', 'sans-serif'],
          mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace']
        },
        minHeight: { touch: '44px' },
        minWidth: { touch: '44px' }
      }
    }
  };
})();
