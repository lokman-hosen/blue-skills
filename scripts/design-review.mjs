#!/usr/bin/env node
/**
 * Visual review of /_design and every layout preview with Playwright.
 *
 *   node scripts/design-review.mjs [--base=http://localhost:8080] [--out=storage/design-review]
 *                                   [--locales=en,bn,ur] [--themes=light,dark] [--widths=390,1440]
 *                                   [--pages=/a,/b] [--email=… --password=…] [--guest] [--phone=+880…]
 *
 * Signs in as the local superuser (or --email, or nobody with --guest), then for each page × locale × theme × width it records
 * console errors and CSP violations, axe WCAG 2.2 AA violations, horizontal overflow, elements that spill out of the viewport and text with
 * insufficient contrast (WCAG AA), and saves a full-page screenshot. Exits 1 on any finding.
 */
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import AxeBuilder from '@axe-core/playwright';
import { chromium } from 'playwright';

const args = Object.fromEntries(
    process.argv.slice(2).map((arg) => {
        const [key, ...rest] = arg.replace(/^--/, '').split('=');
        const value = rest.length > 0 ? rest.join('=') : undefined;

        return [key, value ?? 'true'];
    }),
);

const base = args.base ?? 'http://localhost:8080';
const out = args.out ?? 'storage/design-review';
const locales = (args.locales ?? 'en,bn,ur').split(',');
const themes = (args.themes ?? 'light,dark').split(',');
const widths = (args.widths ?? '390,1440').split(',').map(Number);
const pages = (args.pages ?? '/_design,/_design/layouts/public,/_design/layouts/auth,/_design/layouts/worker,/_design/layouts/org,/_design/layouts/admin').split(',');
const email = args.email ?? 'superuser@blueskills.test';
const password = args.password ?? 'password';
const guest = args.guest === 'true';
/** Worker sign-in: E.164 number of a seeded worker, e.g. +8801711000001 (Rahim). */
const phone = args.phone;

/**
 * Worker phone sign-in. Needs OTP_CHANNEL=log and a running queue worker; the code is read
 * from storage/logs/laravel.log.
 */
async function signInByPhone(page) {
    const country = phone.startsWith('+880') ? 'BD' : phone.startsWith('+977') ? 'NP' : phone.startsWith('+91') ? 'IN' : phone.startsWith('+92') ? 'PK' : 'LK';
    const dial = { BD: '+880', NP: '+977', IN: '+91', PK: '+92', LK: '+94' }[country];

    await page.goto(`${base}/join/phone`);
    await page.selectOption('select[name=country]', country);
    await page.fill('input[name=phone]', phone.slice(dial.length));
    // Only codes logged after this request count; older ones are already used or expired.
    const logOffset = readFileSync('storage/logs/laravel.log', 'utf8').length;
    await Promise.all([page.waitForURL((url) => url.pathname === '/join/verify'), page.click('button[type=submit]')]);

    let code = null;

    for (let attempt = 0; attempt < 40 && !code; attempt++) {
        const log = readFileSync('storage/logs/laravel.log', 'utf8').slice(logOffset);
        const matches = [...log.matchAll(new RegExp(`sign-in code for \\${phone}: (\\d{6})`, 'g'))];
        code = matches.at(-1)?.[1] ?? null;

        if (!code) {
            await new Promise((resolve) => setTimeout(resolve, 250));
        }
    }

    if (!code) {
        throw new Error(`No sign-in code for ${phone} in storage/logs/laravel.log. Is a queue worker running?`);
    }

    await page.locator('input[name=code]').pressSequentially(code);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.startsWith('/join'), { timeout: 15000 }),
        page.locator('form button[type=submit]').first().click(),
    ]);
}

mkdirSync(out, { recursive: true });

/** Runs in the page: overflow and contrast findings. */
function inspect() {
    const findings = [];
    const viewport = document.documentElement.clientWidth;

    if (document.documentElement.scrollWidth > viewport + 1) {
        findings.push({ type: 'overflow', detail: `page scrollWidth ${document.documentElement.scrollWidth} > ${viewport}` });
    }

    const describe = (element) => {
        const id = element.id ? `#${element.id}` : '';
        const cls = typeof element.className === 'string' ? `.${element.className.trim().split(/\s+/).slice(0, 3).join('.')}` : '';

        return `${element.tagName.toLowerCase()}${id}${cls}`.slice(0, 120);
    };

    const scrollsHorizontally = (element) => {
        for (let node = element.parentElement; node; node = node.parentElement) {
            const style = getComputedStyle(node);

            if (['auto', 'scroll', 'hidden', 'clip'].includes(style.overflowX)) {
                return true;
            }
        }

        return false;
    };

    for (const element of document.querySelectorAll('body *')) {
        const rect = element.getBoundingClientRect();

        if (rect.width === 0 || rect.height === 0 || getComputedStyle(element).position === 'fixed') {
            continue;
        }

        if ((rect.right > viewport + 1 || rect.left < -1) && !scrollsHorizontally(element)) {
            findings.push({ type: 'spill', detail: `${describe(element)} spans ${Math.round(rect.left)}–${Math.round(rect.right)}px` });
        }
    }

    const parse = (color) => {
        const canvas = document.createElement('canvas').getContext('2d');
        canvas.fillStyle = color;
        canvas.fillRect(0, 0, 1, 1);
        const [r, g, b, a] = canvas.getImageData(0, 0, 1, 1).data;

        return { r, g, b, a: a / 255 };
    };
    const luminance = ({ r, g, b }) => {
        const channel = (value) => {
            const c = value / 255;

            return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
    };
    const backgroundOf = (element) => {
        const layers = [];

        for (let node = element; node; node = node.parentElement) {
            const style = getComputedStyle(node);

            if (style.backgroundImage !== 'none' && !style.backgroundImage.includes('gradient')) {
                return null; // image background: skip
            }

            const color = parse(style.backgroundColor);

            if (color.a > 0) {
                layers.push(color);

                if (color.a >= 1) {
                    break;
                }
            }
        }

        return layers.reverse().reduce(
            (under, over) => ({
                r: over.r * over.a + under.r * (1 - over.a),
                g: over.g * over.a + under.g * (1 - over.a),
                b: over.b * over.a + under.b * (1 - over.a),
            }),
            { r: 255, g: 255, b: 255 },
        );
    };

    const seen = new Set();

    for (const element of document.querySelectorAll('body *')) {
        const text = [...element.childNodes]
            .filter((node) => node.nodeType === Node.TEXT_NODE)
            .map((node) => node.textContent.trim())
            .join('');

        if (!text) {
            continue;
        }

        const style = getComputedStyle(element);
        const rect = element.getBoundingClientRect();

        if (rect.width === 0 || style.visibility === 'hidden' || Number(style.opacity) < 0.1 || element.closest('[aria-hidden="true"], .sr-only')) {
            continue;
        }

        const background = backgroundOf(element);

        if (!background) {
            continue;
        }

        const foreground = parse(style.color);
        const blended = {
            r: foreground.r * foreground.a + background.r * (1 - foreground.a),
            g: foreground.g * foreground.a + background.g * (1 - foreground.a),
            b: foreground.b * foreground.a + background.b * (1 - foreground.a),
        };
        const [light, dark] = [luminance(blended), luminance(background)].sort((x, y) => y - x);
        const ratio = (light + 0.05) / (dark + 0.05);
        const size = parseFloat(style.fontSize);
        const bold = Number(style.fontWeight) >= 700;
        const large = size >= 24 || (size >= 18.66 && bold);
        const disabled = element.closest('[aria-disabled="true"], :disabled');
        const required = large ? 3 : 4.5;

        if (!disabled && ratio < required) {
            const key = `${describe(element)}|${ratio.toFixed(2)}`;

            if (!seen.has(key)) {
                seen.add(key);
                findings.push({ type: 'contrast', detail: `${ratio.toFixed(2)}:1 (needs ${required}) "${text.slice(0, 40)}" ${describe(element)}` });
            }
        }
    }

    return findings;
}

const browser = await chromium.launch();
/** Session cookies from the first sign-in, reused for every theme (avoids the OTP resend wait). */
let storageState;
const report = [];

try {
    for (const theme of themes) {
        const context = await browser.newContext({ colorScheme: theme, viewport: { width: 1440, height: 900 }, storageState });
        await context.addInitScript(() => {
            localStorage.setItem('appearance', 'system');
        });
        const page = await context.newPage();
        const errors = [];
        // Report-only CSP violations (D-105) are logged as info, so match them by text.
        page.on('console', (message) => (message.type() === 'error' || message.text().includes('Content Security Policy')) && errors.push(message.text()));
        page.on('pageerror', (error) => errors.push(error.message));

        if (storageState) {
            // Already signed in through the shared session.
        } else if (phone) {
            await signInByPhone(page);
        } else if (!guest) {
            await page.goto(`${base}/login`);
            await page.fill('input[name=email]', email);
            await page.fill('input[name=password]', password);
            await Promise.all([page.waitForURL((url) => !url.pathname.startsWith('/login')), page.click('button[type=submit]')]);
        }

        if (!guest && !storageState) {
            storageState = await context.storageState();
        }

        for (const locale of locales) {
            for (const width of widths) {
                await page.setViewportSize({ width, height: width < 768 ? 844 : 900 });

                for (const path of pages) {
                    errors.length = 0;
                    await page.goto(`${base}${path}${path.includes('?') ? '&' : '?'}lang=${locale}`, { waitUntil: 'networkidle' });
                    await page.evaluate(() => document.fonts.ready);
                    const findings = await page.evaluate(inspect);

                    for (const error of errors) {
                        findings.push({ type: 'console', detail: error });
                    }

                    // WCAG 2.2 AA rules from axe (D-109). Text contrast is checked above with the
                    // design tokens in mind, so axe's colour-contrast rule is left out here.
                    const axe = await new AxeBuilder({ page })
                        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
                        .disableRules(['color-contrast'])
                        .analyze();

                    for (const violation of axe.violations) {
                        findings.push({
                            type: 'a11y',
                            detail: `${violation.id} (${violation.impact}, ${violation.nodes.length}×) ${violation.nodes[0]?.target.join(' ')}`,
                        });
                    }

                    const name = `${path.replaceAll('/', '_').replace(/^_+/, '') || 'home'}-${locale}-${theme}-${width}`;
                    await page.screenshot({ path: join(out, `${name}.png`), fullPage: true });
                    report.push({ path, locale, theme, width, findings });
                    console.log(`${findings.length === 0 ? 'ok  ' : 'FAIL'} ${name}${findings.length ? ` (${findings.length})` : ''}`);

                    for (const finding of findings.slice(0, 8)) {
                        console.log(`     ${finding.type}: ${finding.detail}`);
                    }
                }
            }
        }

        await context.close();
    }
} finally {
    await browser.close();
}

writeFileSync(join(out, 'report.json'), JSON.stringify(report, null, 2));
process.exit(report.some((entry) => entry.findings.length > 0) ? 1 : 0);
