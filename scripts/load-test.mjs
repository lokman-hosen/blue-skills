#!/usr/bin/env node
/**
 * Small load test for staging (D-106). Virtual users browse the public job search and Truth
 * Cards; with --email they also sign in as an organization member and open the workspace pages.
 * Prints requests, errors and p50/p95/max latency per page. No dependencies beyond Node 22.
 *
 *   node scripts/load-test.mjs --base=https://staging.example --users=25 --seconds=60 \
 *        [--email=owner@abc-contracting.test --password=password]
 *
 * The worker apply flow is not included: every apply needs its own worker signed in by a phone
 * code. Run against staging, never production, and raise BLUESKILLS_WRITES_PER_MINUTE /
 * guest limits there if sign-in is load tested too.
 */

const args = Object.fromEntries(
    process.argv.slice(2).map((arg) => {
        const [key, ...rest] = arg.replace(/^--/, '').split('=');
        return [key, rest.join('=') || 'true'];
    }),
);

const base = (args.base ?? 'http://localhost:8080').replace(/\/$/, '');
const users = Number(args.users ?? 10);
const seconds = Number(args.seconds ?? 30);
const searches = ['electrician', 'hvac', 'plumber', 'welder', 'dubai', 'riyadh', ''];
const results = new Map();

function record(name, ms, ok) {
    const entry = results.get(name) ?? { times: [], errors: 0 };
    entry.times.push(ms);
    if (!ok) entry.errors += 1;
    results.set(name, entry);
}

/** A cookie jar per virtual user, so sessions and CSRF tokens work like a browser. */
function client() {
    const jar = new Map();

    async function request(path, init = {}) {
        const response = await fetch(base + path, {
            redirect: 'manual',
            ...init,
            headers: {
                cookie: [...jar].map(([key, value]) => `${key}=${value}`).join('; '),
                ...(init.headers ?? {}),
            },
        });
        for (const cookie of response.headers.getSetCookie()) {
            const [pair] = cookie.split(';');
            const [key, ...value] = pair.split('=');
            jar.set(key.trim(), value.join('='));
        }
        return response;
    }

    return { request, jar };
}

async function timed(name, run) {
    const started = performance.now();
    try {
        const response = await run();
        await response.arrayBuffer();
        record(name, performance.now() - started, response.status < 400);
        return response;
    } catch {
        record(name, performance.now() - started, false);
        return null;
    }
}

async function signIn(http) {
    await timed('GET /login', () => http.request('/login'));
    const token = decodeURIComponent(http.jar.get('XSRF-TOKEN') ?? '');
    const response = await timed('POST /login', () =>
        http.request('/login', {
            method: 'POST',
            headers: { 'content-type': 'application/json', 'x-xsrf-token': token, accept: 'text/html' },
            body: JSON.stringify({ email: args.email, password: args.password ?? 'password' }),
        }),
    );
    return response !== null && response.status === 302;
}

async function jobCodes(http) {
    const response = await http.request('/jobs');
    const html = await response.text();
    return [...new Set(html.match(/[A-Z]{2,3}-[A-Z]{2}-\d{4}|VOL-\d{5}/g) ?? [])].slice(0, 20);
}

async function virtualUser(index, until, codes) {
    const http = client();
    const member = args.email && index % 3 === 0 && (await signIn(http));

    while (Date.now() < until) {
        const q = searches[Math.floor(Math.random() * searches.length)];
        await timed('GET /jobs?q=…', () => http.request(`/jobs?q=${encodeURIComponent(q)}`));
        if (codes.length > 0) {
            const code = codes[Math.floor(Math.random() * codes.length)];
            await timed('GET /jobs/{code}', () => http.request(`/jobs/${code}`));
        }
        if (member) {
            for (const path of ['/dashboard', '/org/jobs', '/org/talent?q=wiring', '/org/candidates']) {
                await timed(`GET ${path}`, () => http.request(path));
            }
        }
    }
}

function percentile(sorted, p) {
    return sorted[Math.min(sorted.length - 1, Math.floor((p / 100) * sorted.length))];
}

const codes = await jobCodes(client());
const until = Date.now() + seconds * 1000;
console.log(`${users} users for ${seconds}s against ${base} (${codes.length} job codes)…`);
await Promise.all(Array.from({ length: users }, (_, index) => virtualUser(index, until, codes)));

let total = 0;
console.log('\npage                         requests  errors     p50     p95     max');
for (const [name, { times, errors }] of [...results].sort()) {
    const sorted = times.sort((a, b) => a - b);
    total += times.length;
    console.log(
        `${name.padEnd(28)} ${String(times.length).padStart(8)} ${String(errors).padStart(7)} ` +
            [50, 95, 100].map((p) => `${Math.round(percentile(sorted, p))}ms`.padStart(7)).join(' '),
    );
}
console.log(`\n${total} requests, ${(total / seconds).toFixed(1)} per second`);
