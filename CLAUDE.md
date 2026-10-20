# BLUESKILLS.AE — Project guide for Claude

BLUESKILLS.AE is a multilingual, mobile-first global skilled-workforce marketplace.
Workers build a worker-owned **Global Skill Passport** by voice or typing, add a 30-second
**SkillShow** video, browse jobs that each show a **Job Truth Card**, apply with per-item document
consent, and track recruitment and mobility (permit, visa, travel, joining). Employers, licensed
recruitment agencies, training institutes and BLUESKILLS staff each have their own workspace.
Worker registration is free; employers and job posters pay.

Source of truth for product behaviour:
- `docs/spec/BLUESKILLS_AE_Portal_Architecture.docx` — product spec (sections referenced as §n)
- `docs/spec/BLUESKILLS_Concept_User_Journey_Visual_Mockups_1.pptx` — wireframe deck
- `docs/design/` — locked HTML design pages + `prototype-v1.html` (visual and flow reference)
- `docs/decisions.md` — decisions made during development (append, never rewrite history)

If the spec and this file disagree, ask. If the spec and the HTML design disagree on behaviour,
the spec wins; on visuals, the HTML design wins.

---

## Stack

- **Backend:** Laravel 13 (PHP 8.3+), PostgreSQL 16+, Redis, Laravel Horizon (queues)
- **Frontend:** Laravel React starter kit — Inertia 3, React 19, TypeScript, Tailwind 4, shadcn/ui,
  Wayfinder (typed routes)
- **Auth:** Fortify (organisation users: email + password + 2FA) plus a custom phone-OTP flow for workers
- **Key packages:** spatie/laravel-permission (teams mode), spatie/laravel-activitylog,
  spatie/laravel-medialibrary, spatie/laravel-model-states, spatie/laravel-data,
  spatie/laravel-typescript-transformer, propaganistas/laravel-phone, pbmedia/laravel-ffmpeg
- **Later:** Laravel Scout + Meilisearch (search), Laravel Reverb (realtime), Laravel Cashier (billing),
  Prism PHP (LLM extraction for Voice-to-CV)
- **Quality:** Pest, Larastan (level 6+), Pint, ESLint + Prettier, `tsc --noEmit`
- **Hosting target:** VPS / Laravel Cloud in a UAE region behind Cloudflare. Not shared hosting.

## Commands

```bash
./vendor/bin/sail up -d          # pgsql, redis, mailpit, meilisearch; app on http://localhost:8080
./vendor/bin/sail artisan …      # host PHP has no pdo_pgsql/redis; run artisan and Pest in Sail
./vendor/bin/sail php vendor/bin/pest --parallel
composer run dev                 # app + vite + queue + logs
php artisan test --parallel      # Pest
./vendor/bin/pint                # PHP format
./vendor/bin/phpstan analyse     # static analysis
npm run lint && npm run types    # ESLint + tsc
php artisan typescript:transform # regenerate TS types from Data classes
php artisan migrate:fresh --seed # reset local DB with sample data
npm run format                   # Prettier
npm run build && npm run design:review  # Playwright review of /_design (en/bn/ur, light/dark, 390/1440)
npm run design:review -- --phone=+8801711000001 --pages=/passport  # as a worker (needs a queue worker)
```

Worker sign-in is `/join` (phone + code). Locally `OTP_CHANNEL=log` writes the code to
`storage/logs/laravel.log`; `BLUESKILLS_ENFORCE_STAFF_2FA=false` in `.env` skips the staff 2FA requirement
on your machine only (tests always enforce it). Sign-in codes, Voice-to-CV answers and SkillShow videos are
processed on the queue: run `sail artisan queue:work` (or `composer run dev`). `SPEECH_TO_TEXT_DRIVER=sample`
answers every recording with the prototype's sample sentence; SkillShow checks need ffmpeg (in Sail).
Seeded worker Rahim Uddin signs in with `+8801711000001` and has a complete Skill Passport.

Local logins after seeding (password `password`): `superuser@blueskills.test` (staff),
`owner@abc-contracting.test` (employer Owner), `sara@abc-contracting.test` (employer HR Admin), `imran@northstar.test` (agency case officer),
`nusrat@meghna.test` (institute placement officer). `/_design` is the staff-only component gallery.

Run tests, Pint, PHPStan and `npm run types` before every commit. Never commit failing checks.

---

## Architecture

### Backend: modular by domain

```
app/
  Domain/
    Identity/        users, phone OTP, sessions, roles
    Organization/    organizations, members, invitations, verification requests
    Worker/          worker profiles, experiences, skills, languages, SkillShows, voice answers
    Taxonomy/        sectors → occupations → specializations → skills
    Jobs/            jobs, job costs, Truth Card, mandates, job lifecycle
    Applications/    applications, profile snapshots, consents, interviews, offers
    Mobility/        mobility cases, stage templates, case documents
    Institute/       courses, cohorts, graduates, attestations
    Verification/    verification queue items, evidence, decisions, reason codes
    Trust/           reports, trust cases, suspensions, appeals
    Messaging/       conversations, messages, notifications
    Billing/         plans, credits, subscriptions, invoices
    Config/          countries, languages, country rules
  Http/Controllers/{Public,Worker,Employer,Agency,Institute,Admin}/
```

Each domain folder holds: `Models/`, `Actions/`, `Data/` (laravel-data DTOs), `Enums/`,
`States/`, `Policies/`, `Events/`, `Jobs/` (queued), `Rules/`.

Rules:
- **Controllers are thin.** Validate with a FormRequest, authorise with a Policy, call one Action,
  return an Inertia response or redirect. No business logic in controllers or React.
- **Actions** are single-purpose classes with one `handle()` method (`SubmitJobForReview`,
  `CreateApplicationSnapshot`). They are the only place business rules live.
- **Inertia props are always laravel-data objects**, never raw models. Those Data classes generate
  the TypeScript types the frontend uses.
- **Status fields use PHP backed enums + spatie model-states** with explicit allowed transitions.
- Wrap multi-table writes in `DB::transaction()`.
- Anything slow (media, speech-to-text, notifications, exports) runs in a queued Job. Never in a request.

### Frontend

```
resources/js/
  components/ui/          shadcn primitives
  components/bs/          BLUESKILLS components (TruthCard, StatusChip, StageTracker, SkillPassportCard…)
  layouts/                PublicLayout, WorkerLayout, OrgLayout, AdminLayout, AuthLayout
  pages/{public,auth,worker,employer,agency,institute,admin}/…
  lib/i18n.ts             t() helper reading translations shared by Inertia
  types/generated.d.ts    generated from Data classes, do not edit by hand
  layouts/navigation.ts   nav per workspace; items without a route render as "Soon"
```

- Layout is chosen by page folder in `app.tsx` (`public/`, `auth/`, `worker/`, `org|employer|agency|institute/`,
  `admin/`; `settings/` and anything else use the signed-in user's workspace).
- Status chips take `StatusData` (`value`, `label`, `tone`); model-states expose `label()` and `tone()`.
- Translation keys are dotted (`truth_card.row.salary`) with `:name` placeholders; add every new key to
  `lang/en.json` and `lang/bn.json` (a test keeps them in sync) and list unreviewed text in
  `docs/translation-todo.md`.
- Staff roles: `$user->assignStaffRole()` / `hasStaffPermission()`. Organization roles: wrap calls in
  `TeamContext::run($organizationId, …)`. See `docs/decisions.md` D-012.

- Use Wayfinder route helpers, never hard-coded URLs.
- Design tokens live in `resources/css/app.css` under `@theme` (ported from the locked HTML design).
  Use token names (`bg-surface`, `text-muted`, `bg-brand`…), never raw hex values.
- **Mobile first.** Every page must work at 360px with no horizontal scroll, and up to 1440px+.
  Tables become stacked cards below `md`. Filters become a bottom sheet on mobile. Touch targets ≥ 44px.
- **RTL:** Urdu (later Arabic) sets `dir="rtl"`. Use logical utilities only (`ms-`, `me-`, `ps-`,
  `pe-`, `start-`, `end-`, `text-start`). Never `ml-`/`mr-`/`left-`/`right-` for layout.
- Accessibility: WCAG 2.2 AA, labels on every input, visible focus, keyboard-operable dialogs.
- Every list page has empty, loading and error states. Error messages say what went wrong and how to fix it.

### Data conventions

- Primary keys: ULIDs (`HasUlids`) on all domain tables. Never expose sequential IDs in URLs.
- Money: `amount_minor` (bigint) + `currency` (ISO 4217 char(3)). Never floats.
- Phones: E.164 via laravel-phone. Times: `timestamptz`, stored in UTC, displayed in the user's zone.
- Every user and organization has `data_region` (default `ae`) for future data residency.
- Soft deletes only where the spec needs recovery or audit (users, organizations, jobs, documents).
- Sensitive files (passport scans, IDs, contracts) go to a **private** disk and are served only
  through signed, short-lived URLs after a policy + consent check.
- Every state change on verification, jobs, applications, mobility and trust is written to the
  activity log with actor, reason code and before/after values.

### i18n

- Backend strings: `lang/{locale}.json`. The current locale's strings are shared to React via
  Inertia and read with `t('key')`.
- Launch locales: `en`, `bn`. Next: `hi`, `ur` (RTL), `ne`; then `si`, `ta`.
- Never machine-translate silently. Missing translations fall back to English and are listed in
  `docs/translation-todo.md`.

---

## Product rules (never break these)

1. **The worker owns the profile.** Organizations get role-scoped *projections* of it, never ownership.
2. **Voice-to-CV never saves unconfirmed data.** Speech → transcript → extracted fields → worker
   confirms or edits → save. Inferred claims are never published silently.
3. **SkillShow** is max 30 seconds, moderated before publishing, and always labelled
   "worker-provided demonstration, not certification".
4. **Job Truth Card** renders the same structured job object everywhere. Missing values show
   "Not disclosed", never a guess. "Verified" badges appear only for checks actually performed.
   Every worker-paid cost is itemised with who pays.
5. **Material job changes** (pay, employer, worker costs, conditions) send the job back to review and
   notify existing applicants.
6. **Passport scans and phone numbers never appear in search results.** Documents are shared per
   application, per item, with explicit consent that is recorded and revocable.
7. **An application stores a dated profile snapshot.** Later profile edits do not change it.
   Duplicate applications to the same job are blocked.
8. **An application and a mobility case are separate entities.** A mobility case is created only
   after a selection/offer milestone, using a country-specific stage template.
9. **Evidence states are distinct:** self-declared, uploaded/unreviewed, verification pending,
   issuer verified, expired, rejected. Each evidence item is verified independently.
10. **Agencies need a verified licence AND a verified mandate** before publishing a job for an employer.
11. **Institutes can attest training but cannot edit a worker's career history** or see documents
    without permission. Graduates must accept the invitation.
12. **Matching is explained:** "X of Y requirements met" with the list. No opaque scores, no ranking
    by video alone.
13. **Worker fees and platform billing are separate ledgers.** Allowed worker costs come from
    country rules (`Config` domain), not hard-coded.
14. No autonomous AI decisions in recruitment or immigration. AI only transcribes, extracts
    (for the worker to confirm) and translates.

## Roles

- Worker
- Employer: Owner, HR Admin, Recruiter, Hiring Manager, Viewer
- Agency: Agency Owner, Case Officer, Compliance Reviewer
- Institute: Institute Owner, Trainer, Assessor, Placement Officer
- BLUESKILLS staff: Superuser, Verification Officer, Trust Officer, CRM Agent, Finance Officer

Organization roles use spatie-permission **teams** (team = organization). Staff roles are global.
Every controller action is covered by a Policy and a test for at least one allowed and one denied role.

---

## Module order and status

Update this table when a module is done.

| # | Module | Status |
|---|--------|--------|
| 0 | Foundation: project, DB core, design system, layouts, CI | ✓ |
| 1 | Auth & roles: worker OTP, org email + 2FA, staff invites, team roles | ✓ |
| 2 | Worker profile: onboarding, Skill Passport, Voice-to-CV, SkillShow | ✓ |
| 3 | Organizations & jobs: employer verification, job wizard, Truth Card, job review, public search | ☐ |
| 4 | Applications: apply + snapshot + consent, tracker, pipeline, talent search, shortlists | ☐ |
| 5 | Messaging & notifications: in-app, WhatsApp, SMS, email | ☐ |
| 6 | Agency & mobility: licence, mandates, mobility cases, documents | ☐ |
| 7 | Institutes: cohorts, invites, attestation, talent pools | ☐ |
| 8 | Admin & trust: verification queues, reports, cases, config, taxonomy | ☐ |
| 9 | CRM, analytics, billing | ☐ |
| 10 | Hardening & pilot launch | ☐ |

**Definition of done for a module:** migrations, models, policies, actions, FormRequests, controllers,
Inertia pages (mobile + desktop, light + dark, en + bn), seeders with sample data, Pest feature tests
for happy path + permission denial + validation, PHPStan/Pint/types clean, `docs/decisions.md`
updated, deployed to staging.

## How to work in this repo

- Start each task in plan mode: read the relevant spec sections and existing code, propose the plan
  (files, migrations, routes, pages, tests), then wait for approval.
- One module (or one clearly scoped part of a module) per branch: `feat/<module>-<part>`.
- Small commits with Conventional Commit messages.
- Do not add a package that isn't listed above without asking.
- Do not change a migration that has already been merged; write a new one.
- Do not invent real company names. Seed data uses clearly fictional organizations.
- When unsure about a product rule, ask instead of guessing, and record the answer in `docs/decisions.md`.
