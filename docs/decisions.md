# Decisions

Append-only log of decisions made during development. Never rewrite an entry; add a new one that
supersedes it. Format: date, module, decision, why.

---

## 2026-10-20 · Module 0 · Foundation

### D-001 · Starter kit from `dev-main`
The tagged `laravel/react-starter-kit` release (v1.0.1) still ships Laravel 12 + Ziggy. We created
the app from the kit's `main` branch (what `laravel new --react` pulls), which gives Laravel 13,
Inertia 3, React 19, Tailwind 4, shadcn/ui, Fortify (2FA + passkeys) and Wayfinder.

### D-002 · Model for the `jobs` table is `JobPosting`
The domain table stays `jobs` (spec and brief), but the Eloquent model is `JobPosting` so it is not
confused with queued jobs (`App\Domain\*\Jobs`). The framework's database-queue `jobs` table is
dropped because queues run on Redis/Horizon. `job_batches` and `failed_jobs` stay.

### D-003 · ULID primary keys everywhere in the domain
All domain tables use ULID primary keys, including `users` and `organizations`. Consequences:
spatie-permission `model_id` and `organization_id` (team key) are ULID columns; medialibrary and
activitylog morphs use `ulidMorphs`. Package lookup tables (`roles`, `permissions`, `media`,
`activity_log`) keep bigint ids because they are never exposed in URLs.

### D-004 · spatie-permission teams: team = organization
`permission.teams = true`, `team_foreign_key = organization_id`. Staff roles are global (see Part B
entry for how staff assignments are stored).

### D-005 · ESLint + Prettier instead of the kit's `vite-plus` check
The new starter kit lints/formats with `vp check` (Oxlint/Oxfmt). CLAUDE.md and the Module 0 brief
require ESLint + Prettier, so `npm run lint` is ESLint and `npm run format` is Prettier. `vite-plus`
is still used for `dev`/`build`. ESLint also forbids physical-direction Tailwind utilities
(`ml-`, `pr-`, `left-`, `text-right`…) outside `components/ui` to keep RTL working.

### D-006 · PHPStan level 6
The kit ships Larastan at level 7; the Module 0 brief asks for level 6 (CLAUDE.md: "6+"). We run 6
and may raise it once the domain code settles.

### D-007 · Existing starter-kit tests stay as PHPUnit classes
Pest runs them unchanged. All new tests are written in Pest syntax. The class-based tests will be
rewritten when Module 1 replaces the auth/settings flows.

### D-008 · Redis for cache, session and queue; no database cache/session tables
`cache`, `cache_locks` and `sessions` migrations are removed. Tests use the array drivers.

### D-009 · Sail host ports
Local Postgres/web servers commonly occupy 5432/80, so `.env.example` forwards Sail to
`APP_PORT=8080`, `FORWARD_DB_PORT=54320`, `FORWARD_REDIS_PORT=63790`.

### D-010 · Meilisearch container now, Scout later
Sail includes Meilisearch as requested, but Laravel Scout is a "Later" package in CLAUDE.md and is
not installed in Module 0.

### D-011 · Spec file name
`docs/spec/BLUESKILLS_AE_Portal_Architecture (1).docx` was renamed to
`BLUESKILLS_AE_Portal_Architecture.docx` to match CLAUDE.md. The root `index.html` was an identical
copy of `docs/design/prototype-v1.html` and was removed.

### D-012 · Staff roles use a reserved platform team id
spatie-permission teams mode stores a team id on every role assignment. Staff roles are global, so
they are assigned under `PlatformTeam::ID` (`00000000000000000000000000`), which is never a real
organization. All roles (organization and staff) are defined globally (`organization_id = null`)
and assigned per team. Use `User::assignStaffRole()` / `hasStaffRole()` / `hasStaffPermission()`
and `TeamContext::run($organizationId, …)` instead of setting the team id by hand.

### D-013 · Workers have no spatie role
`users.type` (`worker | org | staff`) is the source of truth for the worker role. Workers never get
a spatie role; organization and staff permissions come from spatie roles.

### D-014 · Lifecycle statuses use spatie model-states; other categories use backed enums
States with transitions (job, application, evidence, verification request, organization
verification, mobility case, report) are model-states classes storing a short string name
(`draft`, `issuer_verified`…), each with `label()` and `tone()` for StatusChip. Categorical fields
without a lifecycle (types, payers, visibility, SkillShow moderation, voice-answer status, mandate
status, mobility stage status) are backed string enums with `label()`.

- Job lifecycle follows spec §5: draft → submitted → under_review → published → paused/closed →
  archived, plus more_information_requested, rejected, suspended. Published/paused jobs can go back
  to `submitted` for material changes (product rule 5). Draft cannot jump to published.
- Application stages follow spec §6. Because stages are configurable per project, any forward move
  between applied … offer is allowed; offer → accepted/declined; rejected and withdrawn are
  terminal and reachable from any open stage.
- Evidence uses the six spec §7 states. Self-declared evidence cannot jump to issuer_verified.

### D-015 · Schema details not spelled out in the brief
- Country and language foreign keys are `char(2)`/`varchar(10)` codes referencing
  `countries.code` / `languages.code` (tables still have ULID ids).
- Job benefits (accommodation, food, transport, medical, airfare) are an enum
  (`provided | allowance | deducted | not_provided`) plus a free-text `*_details` column. `null`
  means "Not disclosed". `deducted` covers housing the worker pays for (e.g. dormitory deductions).
- `joining_window` is stored as `joining_from` + `joining_to` dates.
- Jobs also store `min_experience_years`, `language_requirements` (jsonb list of
  `{code, level}`) and `other_benefits` (spec §5 lists experience and language).
- Organizations store `licence_number` and `licence_expires_on` for agency licences (spec §1).
- `job_costs` has a `description` so items like "Visa application fee" read naturally.
- `documents.expires_at` is a date column (name kept from the brief).
- `skill_show_skill` pivot holds SkillShow skill tags.
- Polymorphic columns store short morph names (`job`, `document`, `organization`…) via an enforced
  morph map in `AppServiceProvider`.
- Money is `amount_minor` in the currency's minor unit. JPY has none, so ¥230,000 is stored as
  230000. Currency-aware formatting is a Module 3 task.
- Profile snapshots refuse updates at the model level (product rule 7).

### D-016 · Seeded sample world
Matches prototype-v1: ABC Contracting, Northstar Manpower, Meghna Technical Training Centre, four
more employers, nine workers (Rahim Uddin with a full passport), five jobs. Behaviour follows the
spec where the prototype differs: SA-HV-0021 is `under_review` because its mandate (MD-1121) is
still pending (product rule 10), and PL-WH-0014 is `under_review` after a material change. All
sample accounts use the password `password`; the sample world never seeds in production.

### D-017 · Taxonomy and seed content are English only
Taxonomy `name` columns support every locale, but only English is seeded. Other locales need human
translation (CLAUDE.md: never machine-translate silently). Rahim's voice answers keep the Bangla
transcripts from the prototype.

### D-018 · Users are soft deleted
"Delete account" in settings soft deletes the user (CLAUDE.md). The starter kit test was updated.
Module 1 must decide how a soft-deleted email/phone can register again (unique indexes).

### D-019 · Larastan reads `casts()`
`parseModelCastsMethod: true` is enabled so enum, state and date attributes are typed from the
model's `casts()` method instead of the raw migration column type.

## 2026-10-20 · Module 0 · Part C (design system, layouts, i18n)

### D-020 · Token names and shadcn mapping
Prototype tokens are Tailwind colours with hyphenated names: `bg`, `surface`, `surface-2`, `line`,
`ink`, `muted`, `brand`, `brand-soft`, `on-brand`, `stamp`, `stamp-soft`, `ok`/`warn`/`bad` (+
`-soft`), `screen`, `on-screen`, `bezel`. Raw values are RGB triplets in `--bs-*` variables under
`:root`/`.light` and `.dark`, read through `@theme inline` so nested theme scopes work (used by
`/_design`). shadcn's variables (`primary`, `accent`, `border`, `sidebar-*`…) are mapped onto these
tokens; shadcn's old `bg-muted` (a background) became `bg-surface-2` because `muted` is the
prototype's grey text colour.

### D-021 · Fonts are self-hosted at build time
`laravel-vite-plugin/fonts` downloads Figtree, Bricolage Grotesque, JetBrains Mono and the Noto
scripts (Bengali, Devanagari, Naskh Arabic, Sinhala, Tamil) from Google Fonts during `npm run
build` and serves them from `public/build`. Only Figtree 400/600 and Bricolage 700 are preloaded;
script fonts load on demand via unicode-range. Metric-matched fallbacks need the optional `fontaine`
package, which is not installed (`optimizedFallbacks: false`).

### D-022 · i18n plumbing
- Keys are dotted (`nav.home`) with Laravel-style `:name` placeholders. `lang/en.json` is the
  complete source; other locales are merged over it, so missing keys show English.
- Selectable locales come from the `languages` table (active + in_review), cached as plain arrays
  (Laravel 13's cache refuses to unserialize objects). Planned languages (ar) are not selectable.
- Resolution order: `?lang=` (kept in the session) → session → user's `locale` → Accept-Language →
  English. `POST /locale` stores the choice on the session and the signed-in user.
- `<html lang dir>` is rendered on the server; Urdu and Arabic are RTL.
- In-review locales (si, ta) show a "waiting for translation review" banner.
- Numbers use Latin digits in every locale (`-u-nu-latn`) so amounts and IDs read consistently.

### D-023 · Worker navigation
Bottom tabs on phones: Home, Jobs, Applications, Passport, More. Messages, Documents, Privacy and
Settings live in the "More" sheet (five tabs fit 360px; the prototype's sixth tab did not). From
`lg` the worker gets a side navigation with every item.

### D-024 · Navigation items for unbuilt screens
Nav entries whose screen belongs to a later module have no route yet; they render as disabled with
a "Soon" label. Each module wires its entries when it ships (CLAUDE.md: no hard-coded URLs).

### D-025 · Placeholder landing screens and the org switcher
`/dashboard` renders `worker/home`, `org/dashboard` or `admin/overview` by user type; each is an
empty state naming the module that fills it. The public home is a minimal hero until Module 3.
`POST /organizations/{organization}/switch` (OrganizationPolicy::switchTo = membership) stores the
current organization in the session; shared props expose `currentOrganization` and
`organizations` as Data objects. Settings pages render inside the user's own workspace layout.

### D-026 · Workers satisfy `verified` with a verified phone
Workers may have no email, so `User::hasVerifiedEmail()` returns the phone verification state for
workers. Module 1 may replace this with a dedicated middleware.

### D-027 · RTL details
ESLint forbids physical Tailwind direction utilities. The shadcn Sheet gained logical `start`/`end`
sides, and the workspace sidebar switches to the right in RTL. Directional icons flip with
`rtl:` utilities.

### D-028 · `/_design` and Playwright review
`/_design` (gate `viewDesignSystem` = staff) shows every component, both themes side by side, and
links to `/_design/layouts/{public|auth|worker|org|admin}`. `npm run design:review` runs Playwright
over those pages for chosen locales, themes and widths and reports console errors, overflow and
WCAG AA contrast. `playwright` is a dev dependency for this.

### D-029 · Starter-kit auth and settings screens remain English
They now use the new layouts, but their copy is still hard-coded English. Module 1 rebuilds auth
(phone OTP + org email/2FA) and will move these strings into `lang/*.json`.

## 2026-10-20 · Module 0 · Part D (review and hand-over)

### D-030 · Three light-mode tokens darkened for WCAG AA
The locked palette's light-mode `stamp`, `warn` and `ok` fell below 4.5:1 on some of the
palette's own backgrounds (stamp 3.6:1, warn 4.0:1, ok 4.5:1 on surface-2). Approved during
review: each is darkened by the smallest amount that passes on every light background:
`stamp` 178 104 12 → 151 88 10, `warn` 166 98 0 → 151 89 0, `ok` 20 122 72 → 19 118 70. Dark mode
was already compliant and is unchanged. The prototype values are kept in comments in `app.css`.

### D-031 · English fallback text is isolated inside RTL locales
Until Urdu strings exist, Urdu pages show English fallbacks. The server wraps those fallbacks in
Unicode isolates (U+2068 … U+2069) so punctuation stays at the end of the English run instead of
jumping to the start. Real Urdu strings are not wrapped.

### D-032 · RTL fixes in shadcn primitives
The sidebar's menu badges, menu actions and group actions used physical `right-*` positions and
`text-left`; they now use `end-*` and `text-start`, and menu items with a badge reserve end padding.

### D-033 · Theme toggle icon follows the stored setting
The icon reflects the stored choice (light, dark or system) rather than the resolved theme, which
the server cannot know and which caused a hydration mismatch in dark mode.

## 2026-10-20 · Inputs for Module 1 (answers from the product owner)

### D-034 · Worker OTP goes over WhatsApp first
Phone OTP for workers is sent via WhatsApp initially. It sits behind an OTP channel interface so
SMS can be added later without changing callers (spec §13: no single hard-wired vendor). Locally the
channel logs the code instead of sending it.

### D-035 · Two-factor authentication
Staff must enable 2FA before they can use any admin screen. Organization users are strongly
prompted to enable it (banner plus a nudge after sign-in) but not forced at launch; organization
owners will be able to require it for their team later. Workers use phone OTP and have no 2FA.

### D-036 · Organizations self-register and wait for review
Employers, agencies and institutes sign up themselves (business email + password + 2FA prompt,
then company details and registration evidence). The organization starts as `pending`
verification and a verification request is queued for staff. Until it is approved, members can
complete their profile and draft jobs, but cannot publish jobs or search talent.

### D-037 · Deleted accounts cannot sign in
A soft-deleted user cannot sign in (by phone OTP or email). Their phone number and email stay
reserved by the unique indexes, so the same contact cannot register again; recovery goes through
staff support.

### D-038 · Translation process
1. Developers add the English text to `lang/en.json` and the same key to `lang/bn.json` (a test
   enforces parity); any non-reviewed text is listed in `docs/translation-todo.md`.
2. A native-speaker translator for each launch language writes or corrects the strings (paid
   translators, or bilingual staff at partner institutes such as Meghna for Bangla).
3. A second native speaker reviews them in context on `/_design` and the real screens, using
   `?lang=xx`.
4. Reviewed keys are removed from `docs/translation-todo.md`. Locales whose review is incomplete
   stay `in_review` in the languages table, which shows the "awaiting review" banner.
5. Machine translation is never committed silently. A translation-management tool (e.g. Crowdin,
   Lokalise) can be proposed later if volume grows; it is a new dependency and needs approval.

## 2026-10-21 · Module 1 · Auth & roles

### D-039 · New worker profiles are visible only to jobs they apply to
Spec §16 leaves the default open. New Skill Passports start at `applications_only`; the worker can
widen it later (Module 2).

### D-040 · Worker phone sign-in
- Flow: language → phone (BD, NP, IN, PK, LK dial codes) → 6-digit WhatsApp code → name and
  country of residence. The same flow signs existing workers in.
- `otp_challenges` stores an HMAC of the code (app key), never the code. Codes expire after 10
  minutes and allow 5 attempts. Resend after 60 seconds; at most 5 codes per phone and 20 per IP
  per hour, counted from the table so limits survive cache flushes. Every wrong attempt is
  committed before the error is returned.
- Delivery runs in an encrypted queued job through `OtpChannel` (`WhatsAppCloudOtpChannel` with an
  approved authentication template; `LogOtpChannel` locally, refused in production).
- Numbers that belong to deleted accounts or to organization/staff users get "this number can't be
  used to sign in, contact support". This reveals that the number is blocked, which we accept so
  that real people are not left waiting for a code that never comes.
- Verified-but-unregistered numbers may finish the details step for 30 minutes.

### D-041 · Organization self-registration
Step 1 Fortify account (name, email, password, email verification) → step 2 organization details
(type, legal and display name, country, city, registration number, agency licence + expiry,
optional job title) → step 3 evidence upload. The organization starts `pending`, the user becomes
Owner / Agency Owner / Institute Owner, and a verification request of the matching type is opened.
Evidence files (PDF/JPG/PNG ≤ 10 MB) are media on that verification request on the private disk.
Free-webmail sign-ups are flagged in the request's `notes` for the reviewer. Only members with
`organization.update` can add evidence. `organization.verified` middleware is ready for Module 3.

### D-042 · Team invitations
Invitations are by email with a role valid for the organization type, single-use (SHA-256 of a
48-character token), valid 7 days, and replace earlier pending invitations for the same address.
Only a signed-in organization user whose verified email matches may accept. Organization users
without a workspace see their pending invitations before being asked to register an organization.
An organization always keeps at least one owner. Every change is in the activity log.

### D-043 · Staff accounts
Invitation-only (3-day links) by staff with `staff.manage` (superusers). Accepting creates a verified
staff account and goes straight to the security page to set up 2FA. Staff screens (dashboard,
`/admin`, `/_design`, Horizon) require confirmed 2FA via `staff.2fa`;
`BLUESKILLS_ENFORCE_STAFF_2FA=false` relaxes it on a local machine only. Deactivating a staff member
removes their roles and soft-deletes the account. At least one superuser always remains.

### D-044 · Worker account settings
Workers edit only their name in settings; email, password, 2FA and passkeys are for organization
and staff accounts (`/settings/security` is limited to those types). Deleting a worker account
needs a phone-code confirmation and moves to the Module 2 privacy page.

### D-045 · Permission checks never throw
Policies use spatie's `checkPermissionTo` (false when a permission is missing) instead of
`hasPermissionTo` (throws). The current member's abilities (`organizationAbilities.update`,
`.manageTeam`) are shared so the UI hides links that would end in 403.

### D-046 · Primitives restyled on tokens during review
The destructive button now uses `text-on-brand` (white text failed contrast on the dark-mode `bad`
colour) and the appearance tabs use design tokens instead of the kit's neutral greys.

## Module 2 — Worker profile

### D-047 · Module 2 inputs (owner answers, 2026-10-20)
- **Voice-to-CV providers:** speech-to-text and field extraction sit behind `SpeechToText` and
  `ProfileExtractor` contracts. Until a provider is chosen, a fake transcriber (local/tests) and a
  rules-based extractor (taxonomy names, numbers, country names) are bound. Typing is always offered.
- **Voice recordings** are deleted as soon as the worker confirms or discards the answer, and after
  30 days at the latest. Transcripts stay with the answer.
- **Credentials in Module 2:** certificates and work references upload as evidence items
  (`uploaded_unreviewed`). Passport and ID scans and the Documents page come in Module 4 with
  per-application consent.
- **Review cadence:** once at the end of the module.

### D-048 · Skill Passport schema additions
- `worker_profiles.reference` is the public Passport number `BSK-` + 7 random digits (unique, never
  sequential). Rahim keeps the prototype's `BSK-0004182`.
- `passport_valid_until_year` stores only the year the worker gave ("valid until 2030"), never a
  guessed day. `salary_period` reuses the job `SalaryPeriod` enum.
- `worker_experiences.years` holds "five years in Bangladesh" answers; `started_on` became nullable.
  `yearsWorked()` returns the stated years or rounds them from the dates to the nearest year.
- `voice_answers.typed` marks typed answers; `error_code` and the new `failed` status cover
  speech-to-text failures.
- SkillShow moderation stays an enum (D-014) with two new cases before review: `processing` (the
  server checks length and format and converts the video) and `failed`. `skill_shows.reason_code`
  holds the processing or rejection reason. Uploads, the converted MP4 and the poster are separate
  private media collections.
- Profile visibility changes are written to the activity log.

### D-049 · Onboarding and Voice-to-CV flow
- After `/join/details` a new worker goes to phase A (`/passport/basics`: trade from the taxonomy,
  optional specialization, years of experience), then chooses voice, typing or "later". Nothing
  blocks the portal; the home page shows progress (phone, trade, answers, SkillShow, evidence =
  20 % each) and the next step.
- Seven questions, one per screen: occupation, experience, countries, skills, passport,
  availability, destinations. A recording (≤ 60 s, ≤ 5 MB, WebM/MP4/OGG audio) or typed text
  (≤ 500 characters) is processed on the queue: speech-to-text, then extraction. The page polls
  until it is done.
- The worker sees the transcript and what was understood, then taps "Yes, correct", edits the
  structured fields, or fills them in directly without speaking. Confirmed values replace the
  suggestion on the answer; only one answer per question stays open.
- "Save to my Skill Passport" needs all seven confirmed answers and writes them in one transaction:
  occupation, years, passport status and year, availability, years-only experience per country
  (dated entries added by hand are kept), skills (institute-attested skills are kept) and ordered
  destinations. `profile_completed_at` is stamped the first time the core fields are all present.
- The rules-based extractor matches English taxonomy and country names plus a small reviewed
  vocabulary (`VoiceVocabulary`: Bangla words, city names, number words, Bangla digits). Anything
  it cannot find stays empty for the worker; it never guesses.

### D-050 · Skill Passport editing and credentials
- Sections: about (headline, trade, years, country of residence), work history (years or exact
  dates; dates win), skills, languages (from the `languages` table, so only the eight configured
  languages for now), preferences (passport status and expiry year, availability, up to five
  destinations, optional salary in whole units stored as `amount_minor` using the currency's own
  decimals), photo (JPG/PNG/WebP ≥ 200 px, private disk) and certificates.
- Certificates, training records and work references upload as a `Document` (private) with an
  `uploaded_unreviewed` evidence item. The worker can withdraw them (the document is soft deleted).
- Private media is served by `GET /media/{uuid}`: a 15-minute signed link AND a policy check on the
  owning model (worker profile, SkillShow, voice answer: owner or moderator; document: owner).
  Local disks stream with range support so video can seek; object storage redirects to a 5-minute
  temporary URL.

### D-051 · SkillShow
- One SkillShow per worker; a new upload replaces the old one. Workers record with the phone's
  camera through the file picker or upload a file (MP4, WebM, MOV, 3GP, ≤ 50 MB). The browser
  checks the length first only to save a wasted upload.
- A queued job reads the real duration with ffprobe (≤ 30 s + 0.5 s container tolerance), converts
  to H.264/AAC MP4 at most 720 px tall with a poster frame, deletes the upload and moves the
  SkillShow to `pending`. Unreadable or too-long files become `failed` with `unreadable` or
  `too_long`. CI installs ffmpeg so these tests run for real.
- Changing skill tags or visibility keeps the moderation decision (the video is unchanged).
- Staff with `skill_shows.moderate` (superuser, verification officer) publish or reject from
  `/admin/skillshows`, oldest first. Rejection needs a reason (`not_a_demonstration`,
  `unsafe_work`, `personal_information`, `poor_quality`, `inappropriate`), shown to the worker with
  how to fix it. Decisions are logged with moderator, reason and before/after status. Employers and
  agencies see SkillShows from Modules 3–4, through applications and talent search.

### D-052 · Privacy page and account deletion
- `/passport/privacy` holds profile visibility, consent history and account deletion.
- Choosing "verified employers and agencies" grants a `discovery` consent; narrowing revokes it.
  Rows are never deleted, so the history survives.
- Deleting the account needs a fresh code sent to the worker's phone (`account_deletion`
  purpose). Codes only work for the purpose they were sent for, so a deletion code cannot sign
  anyone in. Deletion revokes every consent, removes recordings, SkillShows, photo and uploaded
  files, hides the Passport and soft deletes the user (D-018), which keeps the number blocked
  (D-037). Application snapshots already shared stay with those applications (product rule 7).
