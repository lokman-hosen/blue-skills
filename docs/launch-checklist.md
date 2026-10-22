# BLUESKILLS pilot launch checklist

Everything that has to be true before real workers and partners use BLUESKILLS, gathered from
`docs/decisions.md` (deploy notes of Modules 5–9 and Module 10, D-104…D-108). Tick items off in
a copy of this file for each environment (staging first, then production).

The pilot shape agreed for launch (D-104): workers register freely; organizations join with a
staff-issued pilot code; one live corridor, Bangladesh → United Arab Emirates; platform billing
is counted but not enforced.

---

## 1. Decisions and approvals (owner)

- [ ] **Hosting and region** chosen: Laravel Cloud if it offers a Middle East region, otherwise
      a UAE VPS (e.g. provisioned with Forge) behind Cloudflare. Not shared hosting.
- [ ] **Operating entity** and which regulated services licensed partners perform (spec §16).
- [ ] **Legal review of the BD → AE corridor**: allowed worker costs, mandatory disclosures and
      document types in Admin › Countries › United Arab Emirates match the advice. Then tick
      "legal review done" there (D-088). Keep every other destination switched off.
- [ ] **Pricing approval** before `BLUESKILLS_BILLING_ENFORCE=true` (D-095, D-102). Sample plans
      are marked "not approved for launch"; create the real plans in Admin › Billing › Plans.
- [ ] **Retention periods** (D-104) reviewed by a lawyer: OTP codes 7 days, campaign visit IPs
      90 days, read notifications 180 days, rejected/withdrawn document files 90 days, closed
      mobility case files 1 year, audit log 2 years. *The automatic clean-up is not built yet*
      (dropped from Module 10); only voice recordings are pruned today (30 days, D-047).
- [ ] **Bangla review** by a native speaker of the worker-facing texts listed in
      `docs/translation-todo.md` (all `bn` strings except six are drafts).
- [ ] **Privacy notice and terms** published (not part of the app yet).

## 2. Provider accounts

| Provider | Needed for | Settings | Notes |
|---|---|---|---|
| WhatsApp Business (Meta Cloud API) | Worker sign-in codes, alerts | `OTP_CHANNEL=whatsapp`, `NOTIFY_WHATSAPP_DRIVER=cloud`, `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_OTP_TEMPLATE`, `WHATSAPP_UPDATE_TEMPLATE`, `WHATSAPP_API_VERSION` | Meta must approve the authentication template and `blueskills_update` (Utility, en/bn/hi/ur/ne; D-071). The `log` drivers refuse to run in production. |
| Twilio | SMS fallback | `NOTIFY_SMS_DRIVER=twilio`, `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_SENDERS` (JSON per country) or `TWILIO_MESSAGING_SERVICE_SID` / `TWILIO_FROM` | Bangladesh sender registration takes time; start early. |
| Email (SMTP, SES, Postmark…) | Organization alerts, invoices, password resets | `MAIL_MAILER`, `MAIL_*`, `MAIL_FROM_ADDRESS=no-reply@…` | SPF, DKIM and DMARC on the sending domain. |
| Speech-to-text | Voice-to-CV | `SPEECH_TO_TEXT_DRIVER` | Provider not chosen yet. Without one, the voice step says it is unavailable and workers type instead; never use `sample` in production. |
| Object storage (S3-compatible, UAE region) | Passport scans, documents, SkillShows | `AWS_*`, `BLUESKILLS_PRIVATE_DISK`, `MEDIA_DISK`, `BLUESKILLS_CSP_MEDIA_SOURCES` | The private bucket must block public access. Add the bucket host to the CSP media sources, or videos and photos won't load. |
| Error monitoring (Sentry) | Seeing production errors | — | Approved (D-104) but **not installed**; was part of the dropped 10c. |
| Backups | Database and private files | — | Approved (D-104) but **not set up**; use the host's managed backups, or add `spatie/laravel-backup`. Test a restore before launch. |

## 3. Server

- [ ] PHP 8.3+ with `pdo_pgsql`, `redis`, `intl`, `gd`, `exif`, `bcmath`, `pcntl`; OPcache on.
- [ ] `ffmpeg` and `ffprobe` installed (SkillShow checks and conversion).
- [ ] PostgreSQL 16+ with the **`pg_trgm`** extension available (search indexes, D-106); the
      migration creates it, so the database user may need permission, or a DBA creates it first.
      Set `max_locks_per_transaction=256` if parallel tests run against it (D-097).
- [ ] Redis for cache, sessions and queues (`CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`
      = `redis`).
- [ ] **Horizon** running under a process monitor (`php artisan horizon`); its dashboard needs
      the `horizon.view` staff permission and confirmed two-factor.
- [ ] **Scheduler**: `* * * * * php artisan schedule:run`. It runs: voice recording pruning
      (03:15), new-applicant digest (06:00 UTC), held alerts (every 5 min), lapsed mandates
      (20:30 UTC), evidence expiry (01:00).
- [ ] **Reverb** (`php artisan reverb:start`) behind TLS, on a host the browser can reach;
      `VITE_REVERB_HOST/PORT/SCHEME` must be that public address **before `npm run build`**.
- [ ] HTTPS everywhere; Cloudflare in front, with `TRUSTED_PROXIES` set (D-108). Without it, every
      visitor looks like one Cloudflare IP and the per-IP limits lock people out.

## 4. Environment

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://blueskills.ae
LOG_LEVEL=warning
BLUESKILLS_DEFAULT_DATA_REGION=ae

# Security (D-105). The CSP is enforced in production; leave it report-only on staging
# (BLUESKILLS_CSP_ENFORCE=false) until the browser console shows no violations.
TRUSTED_PROXIES="*"                       # or Cloudflare's ranges
BLUESKILLS_CSP_MEDIA_SOURCES="https://<bucket host>"
# SESSION_SECURE_COOKIE and SESSION_ENCRYPT default to true in production.
# BLUESKILLS_OTP_MAX_PER_IP_PER_HOUR=100  # raise for a large job fair on one network

# Pilot (D-108)
BLUESKILLS_PILOT=true
BLUESKILLS_BILLING_ENFORCE=false          # until pricing is approved
BILLING_PROVIDER=manual
BILLING_PAYMENT_INSTRUCTIONS="Bank transfer to … quoting the invoice number"

# Never in production
# INERTIA_DEVTOOLS_ENABLED, BLUESKILLS_ENFORCE_STAFF_2FA=false, OTP_CHANNEL=log,
# NOTIFY_*_DRIVER=log, SPEECH_TO_TEXT_DRIVER=sample
```

## 5. First deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build                 # after VITE_REVERB_* are final
php artisan migrate --force
php artisan db:seed --force             # reference data; safe to repeat (D-108)
php artisan blueskills:create-superuser # asks for email, name and password
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
php artisan horizon:terminate           # the process monitor restarts it
php artisan reverb:restart
```

- `db:seed` in production adds countries, corridor rules, languages, taxonomy and roles only; it
  never seeds the fictional sample world and **never overwrites** what staff changed. In pilot
  mode a new database opens only the AE destination.
- The superuser signs in and sets up two-factor authentication (required for every staff
  screen, D-035), then invites the other staff from Admin › Staff.

## 6. Every later deploy

```bash
php artisan down --render="errors::503"   # optional; shows the maintenance page
composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan migrate --force
php artisan db:seed --force               # picks up new roles and permissions
php artisan optimize && php artisan horizon:terminate && php artisan reverb:restart
php artisan up
```

When `public/offline.html` changes, bump `VERSION` in `public/sw.js` (D-107).

## 7. Pilot set-up (staff)

- [ ] Admin › Countries: only United Arab Emirates enabled as a destination; Bangladesh as a
      source. Legal review ticked after section 1.
- [ ] Admin › Organizations › Pilot invite codes: one code per partner, limited to its
      organization type, with an end date. Hand each code over yourself; nothing is emailed.
- [ ] Verify each partner as it registers (licence and mandate for agencies, rule 10).
- [ ] Admin › Marketing: campaign links for the job fairs and partner institutes.
- [ ] Admin › Billing: assign plans, or leave billing for after the pilot.

## 8. Checks on staging before go-live

- [ ] `php artisan test` (Pest), Pint, PHPStan, `npm run types`, `npm run lint` all clean in CI;
      CI also runs `composer audit` and `npm audit`.
- [ ] `npm run design:review -- --base=https://staging…` on the main pages: no console errors and
      **no CSP violations** (they are listed as console findings).
- [ ] `node scripts/load-test.mjs --base=https://staging… --users=25 --seconds=120
      --email=<a staging employer>`: no errors, p95 under 1 s on job search.
- [ ] Worker journey on a real phone over mobile data, in Bangla: sign in with a WhatsApp code,
      Voice-to-CV (or typing), SkillShow upload, apply, see the application.
- [ ] An employer and an agency register with pilot codes; staff verify them; a job is
      submitted, reviewed and published to AE.
- [ ] Kill the network on the phone and reload: the offline page appears.
- [ ] Restore a backup into a scratch database.

## 9. Not built yet (dropped from Module 10, D-104)

These are worth planning before the pilot grows beyond a few partners:

- Worker "download my data" export and automatic clean-up for the retention periods above.
- A breach-response runbook (who decides, who tells workers and partners, within what time).
- A health endpoint beyond `/up`, failed-job alerts and error monitoring.
- Backups (section 2).
