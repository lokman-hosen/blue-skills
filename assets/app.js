/* BLUESKILLS design-pack behaviour. Load in <head> (not deferred) so theme + language + direction
   apply before first paint. Everything else binds on DOMContentLoaded via data attributes:
   data-theme-toggle · select[data-lang-select] · data-i18n="key" · data-open="dialogId" · data-close
   [role=tablist] · data-toast="message" (+ data-toast-key for i18n) · data-sidebar-toggle · data-speak="#selector" · input[data-file-list] */
(function () {
  const html = document.documentElement;
  const store = {
    get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* storage blocked: keep in memory only */ } }
  };
  const LANGS = {
    en: { dir: 'ltr', voice: 'en-US', name: 'English' }, bn: { dir: 'ltr', voice: 'bn-BD', name: 'বাংলা' },
    hi: { dir: 'ltr', voice: 'hi-IN', name: 'हिन्दी' }, ur: { dir: 'rtl', voice: 'ur-PK', name: 'اردو' },
    ne: { dir: 'ltr', voice: 'ne-NP', name: 'नेपाली' }
  };
  // Only worker-facing pages (html[data-i18n-page]) follow the saved language; org/admin stay English.
  const i18nPage = html.hasAttribute('data-i18n-page');

  /* ---------- theme ---------- */
  const savedTheme = store.get('bs-theme');
  if (savedTheme === 'light' || savedTheme === 'dark') html.setAttribute('data-theme', savedTheme);
  const isDark = () => html.getAttribute('data-theme') === 'dark' ||
    (!html.getAttribute('data-theme') && matchMedia('(prefers-color-scheme: dark)').matches);

  /* ---------- language ---------- */
  let lang = i18nPage ? (store.get('bs-lang') || html.lang || 'en') : (html.lang || 'en');
  if (!LANGS[lang]) lang = 'en';
  const applyDir = () => { html.lang = lang; html.dir = LANGS[lang].dir; };
  applyDir();

  function t(key, vars) {
    const dict = window.BS_I18N || {};
    let s = (dict[lang] && dict[lang][key]) ?? (dict.en && dict.en[key]) ?? null;
    // Inserted values (dates, names, numbers) are bidi-isolated so they keep their order inside Urdu text.
    if (s && vars) for (const k in vars) s = s.split(`{${k}}`).join('\u2068' + vars[k] + '\u2069');
    return s;
  }
  function translate(root) {
    root.querySelectorAll('[data-lang-current]').forEach(el => { el.textContent = LANGS[lang].name; });
    root.querySelectorAll('[data-i18n]').forEach(el => {
      const vars = el.dataset.i18nVars ? JSON.parse(el.dataset.i18nVars) : null;
      const s = t(el.dataset.i18n, vars); if (s != null) el.textContent = s;
    });
    ['aria-label', 'placeholder', 'title'].forEach(attr => {
      root.querySelectorAll(`[data-i18n-${attr}]`).forEach(el => {
        const s = t(el.getAttribute(`data-i18n-${attr}`)); if (s != null) el.setAttribute(attr, s);
      });
    });
  }
  function setLang(code) {
    if (!LANGS[code]) return;
    lang = code; store.set('bs-lang', code); applyDir(); translate(document);
    document.dispatchEvent(new CustomEvent('bs:lang', { detail: code }));
    document.querySelectorAll('select[data-lang-select]').forEach(s => { s.value = code; });
    document.querySelectorAll('[data-lang-option]').forEach(el => {
      const on = el.dataset.langOption === code;
      if (el.type === 'radio') el.checked = on; else el.setAttribute('aria-pressed', on);
    });
  }

  /* ---------- icons: inline sprite so <use href="#i-name"> works on file:// and cPanel alike ---------- */
  const P = {
    search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    home: '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
    briefcase: '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M2 13h20"/>',
    list: '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>',
    chat: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    passport: '<rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M8 17h8"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    users: '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 4a4 4 0 0 1 0 8M22 21a7 7 0 0 0-4-6.3"/>',
    settings: '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
    help: '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
    globe: '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20"/>',
    bell: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    back: '<path d="m15 18-6-6 6-6"/>',
    next: '<path d="m9 18 6-6-6-6"/>',
    down: '<path d="m6 9 6 6 6-6"/>',
    arrow: '<path d="M5 12h14M12 5l7 7-7 7"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    'check-circle': '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
    clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    alert: '<path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3"/><path d="M12 9v4M12 17h.01"/>',
    info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
    'x-circle': '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
    shield: '<path d="M20 13c0 5-3.5 7.5-7.7 9a1 1 0 0 1-.6 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.2-2.7a1.2 1.2 0 0 1 1.6 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/>',
    'shield-check': '<path d="M20 13c0 5-3.5 7.5-7.7 9a1 1 0 0 1-.6 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.2-2.7a1.2 1.2 0 0 1 1.6 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
    lock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    mic: '<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M19 10v1a7 7 0 0 1-14 0v-1"/><path d="M12 18v4"/>',
    speaker: '<path d="M11 5 6 9H2v6h4l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/>',
    play: '<path d="m6 3 14 9-14 9z"/>',
    stop: '<rect x="6" y="6" width="12" height="12" rx="2"/>',
    upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
    download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
    camera: '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3"/>',
    file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
    trash: '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
    filter: '<path d="M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4"/>',
    pin: '<path d="M20 10c0 5-5.5 10.2-7.4 11.8a1 1 0 0 1-1.2 0C9.5 20.2 4 15 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
    money: '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    bed: '<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>',
    food: '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>',
    bus: '<path d="M8 6v6M15 6v6M2 12h19.6"/><path d="M18 18h3s.5-1.7.8-2.8c.1-.4.2-.8.2-1.2 0-.4-.1-.8-.2-1.2l-1.4-5C20.1 6.8 19.1 6 18 6H4a2 2 0 0 0-2 2v10h3"/><circle cx="7" cy="18" r="2"/><path d="M9 18h5"/><circle cx="16" cy="18" r="2"/>',
    medical: '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7z"/><path d="M3.2 12h5.3l.5-1 2 4.5 2-7 1.5 3.5h5.3"/>',
    plane: '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
    flag: '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/>',
    building: '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>',
    school: '<path d="M21.4 10.9a1 1 0 0 0 0-1.8L12.8 5.2a2 2 0 0 0-1.7 0L2.6 9.1a1 1 0 0 0 0 1.8l8.6 3.9a2 2 0 0 0 1.7 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
    star: '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
    eye: '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12"/><circle cx="12" cy="12" r="3"/>',
    'eye-off': '<path d="M9.9 4.2A9 9 0 0 1 12 4c6.4 0 10 8 10 8a13 13 0 0 1-1.7 2.7M6.6 6.6A13.5 13.5 0 0 0 2 12s3.6 8 10 8a9.7 9.7 0 0 0 5.4-1.6"/><path d="M14.1 14.1a3 3 0 1 1-4.2-4.2M2 2l20 20"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M6.3 17.7l-1.4 1.4M19.1 4.9l-1.4 1.4"/>',
    moon: '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
    kanban: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 7v7M12 7v4M16 7v9"/>',
    chart: '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
    grid: '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
    mail: '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
    edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    refresh: '<path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M8 16H3v5"/>',
    panel: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/>',
    video: '<path d="m22 8-6 4 6 4z"/><rect x="2" y="6" width="14" height="12" rx="2"/>',
    award: '<circle cx="12" cy="8" r="6"/><path d="M15.5 13 17 22l-5-3-5 3 1.5-9"/>',
    send: '<path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/>',
    more: '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
    wrench: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9z"/>',
    zap: '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
    flame: '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.4-.5-2-1-3-1.1-2.1-.2-4.1 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.2.4-2.3 1-3.3.3 1.4 1.3 2.8 2.5 2.8"/>',
    truck: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.6a1 1 0 0 0-.2-.6l-3.5-4.4A1 1 0 0 0 17.5 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
    box: '<path d="M21 8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.7z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
    fan: '<path d="M10.8 9.8C8.8 7 6 6.6 4.5 8s-.6 4.8 1.6 6.3c2.1 1.4 4.6-.5 5.9-2.3M13.2 14.2c2 2.8 4.8 3.2 6.3 1.8s.6-4.8-1.6-6.3c-2.1-1.4-4.6.5-5.9 2.3M9.8 13.2c-2.8 2-3.2 4.8-1.8 6.3s4.8.6 6.3-1.6c1.4-2.1-.5-4.6-2.3-5.9M14.2 10.8c2.8-2 3.2-4.8 1.8-6.3s-4.8-.6-6.3 1.6c-1.4 2.1.5 4.6 2.3 5.9"/>',
    brick: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M3 14h18M10 4v5M14 9v5M10 14v6"/>',
    car: '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>',
    sparkles: '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M5.6 18.4l2.8-2.8M15.6 8.4l2.8-2.8"/>',
    history: '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
    keyboard: '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h.01M10 8h.01M14 8h.01M18 8h.01M6 12h.01M10 12h.01M14 12h.01M18 12h.01M7 16h10"/>',
    link: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
    copy: '<rect x="8" y="8" width="14" height="14" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
    qr: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/>',
    image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>',
    scale: '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1ZM2 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10M12 3v18M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
    ticket: '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/>',
    paperclip: '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
    ban: '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
    tag: '<path d="M12.59 2.59A2 2 0 0 0 11.17 2H4a2 2 0 0 0-2 2v7.17a2 2 0 0 0 .59 1.42l8.7 8.7a2.43 2.43 0 0 0 3.42 0l6.58-6.58a2.43 2.43 0 0 0 0-3.42Z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
    folder: '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>'
  };
  function injectSprite() {
    if (document.getElementById('bs-sprite')) return;
    const syms = Object.entries(P).map(([n, d]) => `<symbol id="i-${n}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</symbol>`).join('');
    document.body.insertAdjacentHTML('afterbegin', `<svg id="bs-sprite" width="0" height="0" style="position:absolute" aria-hidden="true">${syms}</svg>`);
  }

  /* ---------- toast ---------- */
  const TONE = { ok: 'check-circle', bad: 'x-circle', warn: 'alert', info: 'info' };
  function toast(msg, tone = 'info') {
    let host = document.getElementById('toasts');
    if (!host) {
      host = document.createElement('div'); host.id = 'toasts';
      host.className = 'fixed inset-x-0 bottom-20 lg:bottom-6 z-[90] flex flex-col items-center gap-2 px-4 pointer-events-none';
      host.setAttribute('role', 'status'); host.setAttribute('aria-live', 'polite');
      document.body.appendChild(host);
    }
    const el = document.createElement('div');
    const color = { ok: 'text-ok', bad: 'text-bad', warn: 'text-warn', info: 'text-brand' }[tone] || 'text-brand';
    el.className = 'pointer-events-auto flex items-center gap-3 max-w-md w-full rounded-xl bg-ink text-bg shadow-lg px-4 py-3 text-base font-medium';
    el.innerHTML = `<svg class="w-5 h-5 shrink-0 ${color}" style="filter:brightness(1.6)" aria-hidden="true"><use href="#i-${TONE[tone] || 'info'}"/></svg><span class="min-w-0"></span>`;
    el.querySelector('span').textContent = msg;
    host.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  }

  /* ---------- read aloud (speech synthesis; graceful when unavailable) ---------- */
  function speak(text) {
    if (!('speechSynthesis' in window) || !text) { toast(t('noVoice') || 'Read-aloud is not available on this device', 'warn'); return; }
    speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(text); u.lang = LANGS[lang].voice; speechSynthesis.speak(u);
  }

  /* ---------- tabs (WAI-ARIA, arrow keys follow writing direction) ---------- */
  function initTabs(list) {
    const tabs = [...list.querySelectorAll('[role="tab"]')];
    const select = tab => tabs.forEach(t => {
      const on = t === tab; t.setAttribute('aria-selected', on); t.tabIndex = on ? 0 : -1;
      const panel = document.getElementById(t.getAttribute('aria-controls')); if (panel) panel.hidden = !on;
    });
    tabs.forEach(tab => {
      tab.addEventListener('click', () => select(tab));
      tab.addEventListener('keydown', e => {
        const rtl = html.dir === 'rtl'; let i = tabs.indexOf(tab);
        if (e.key === (rtl ? 'ArrowLeft' : 'ArrowRight')) i = (i + 1) % tabs.length;
        else if (e.key === (rtl ? 'ArrowRight' : 'ArrowLeft')) i = (i - 1 + tabs.length) % tabs.length;
        else if (e.key === 'Home') i = 0; else if (e.key === 'End') i = tabs.length - 1; else return;
        e.preventDefault(); tabs[i].focus(); select(tabs[i]);
      });
    });
  }

  function syncThemeButtons() {
    document.querySelectorAll('[data-theme-toggle]').forEach(b => {
      b.setAttribute('aria-pressed', isDark());
      const use = b.querySelector('use'); if (use) use.setAttribute('href', isDark() ? '#i-sun' : '#i-moon');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    injectSprite();
    translate(document);
    document.querySelectorAll('select[data-lang-select]').forEach(s => { s.value = lang; s.addEventListener('change', () => setLang(s.value)); });
    document.querySelectorAll('[data-lang-option]').forEach(el => {
      const on = el.dataset.langOption === lang;
      if (el.type === 'radio') el.checked = on; else el.setAttribute('aria-pressed', on);
      el.addEventListener(el.type === 'radio' ? 'change' : 'click', () => setLang(el.dataset.langOption));
    });
    syncThemeButtons();
    document.querySelectorAll('[role="tablist"]').forEach(initTabs);

    const shell = document.querySelector('[data-collapsed]');
    if (shell && store.get('bs-sidebar') === 'collapsed') shell.dataset.collapsed = 'true';

    document.addEventListener('click', e => {
      const el = e.target.closest('[data-theme-toggle],[data-open],[data-close],[data-toast],[data-sidebar-toggle],[data-speak],[data-dir-toggle]');
      if (!el) {
        // click on a dialog's backdrop closes it
        if (e.target.tagName === 'DIALOG' && e.target.open) e.target.close();
        return;
      }
      if (el.hasAttribute('data-theme-toggle')) {
        const next = isDark() ? 'light' : 'dark'; html.setAttribute('data-theme', next); store.set('bs-theme', next); syncThemeButtons();
      }
      if (el.dataset.open) { const d = document.getElementById(el.dataset.open); if (d && !d.open) d.showModal(); }
      if (el.hasAttribute('data-close')) el.closest('dialog')?.close();
      if (el.dataset.toast) toast((el.dataset.toastKey && t(el.dataset.toastKey)) || el.dataset.toast, el.dataset.toastTone);
      if (el.hasAttribute('data-sidebar-toggle') && shell) {
        const c = shell.dataset.collapsed !== 'true'; shell.dataset.collapsed = c; store.set('bs-sidebar', c ? 'collapsed' : 'open');
        el.setAttribute('aria-expanded', !c);
      }
      if (el.dataset.speak) { const src = document.querySelector(el.dataset.speak); speak(src ? src.textContent.trim().replace(/\s+/g, ' ') : ''); }
      if (el.hasAttribute('data-dir-toggle')) { html.dir = html.dir === 'rtl' ? 'ltr' : 'rtl'; el.setAttribute('aria-pressed', html.dir === 'rtl'); }
    });

    // File inputs list their chosen files (name + size) in the element named by data-file-list.
    document.querySelectorAll('input[type="file"][data-file-list]').forEach(input => {
      input.addEventListener('change', () => {
        const out = document.getElementById(input.dataset.fileList); if (!out) return;
        const max = Number(input.dataset.maxMb || 10) * 1024 * 1024;
        [...input.files].forEach(f => {
          const ok = f.size <= max;
          const li = document.createElement('li');
          li.className = 'flex items-center gap-3 rounded-xl border border-line bg-surface p-3';
          li.innerHTML = `<svg class="w-6 h-6 shrink-0 ${ok ? 'text-ok' : 'text-bad'}" aria-hidden="true"><use href="#i-${ok ? 'check-circle' : 'x-circle'}"/></svg><div class="min-w-0 flex-1"><p class="font-semibold truncate"></p><p class="text-sm ${ok ? 'text-muted' : 'text-bad'}"></p></div>`;
          li.querySelector('p').textContent = f.name;
          li.querySelector('p + p').textContent = ok ? `${(f.size / 1024 / 1024).toFixed(1)} MB · Ready` : `Too large — max ${input.dataset.maxMb || 10} MB`;
          out.appendChild(li);
        });
        input.value = '';
      });
    });
  });

  window.BS = { toast, setLang, t, speak, translate, get lang() { return lang; } };
})();
