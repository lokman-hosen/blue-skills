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

## Module 3 — Organizations and jobs

### D-053 · Module 3 inputs (owner answers, 2026-10-20)
- **Employer jobs only.** Agencies post jobs in Module 6 together with mandates, because product
  rule 10 needs a verified licence and a verified mandate. Seeded agency jobs still render.
- **Search:** indexed Postgres filters and text match now; Scout + Meilisearch when listings grow.
- **Unlawful worker costs block submission.** The wizard shows the destination's rule, and a job
  whose worker-paid cost category the country does not allow cannot be submitted. A destination
  without cost rules allows no worker-paid costs.
- **Review cadence:** once at the end of the module.

### D-054 · Job review tracking and codes
- Staff review decisions live on `verification_requests` (type `job` or the organization's type)
  with decision, reason code, notes, decider and time. `jobs.submitted_at` and
  `jobs.material_change_at` record the last submission and the last material change.
- Reason codes are one `ReviewReason` enum with organization and job subsets.
- Public job codes are `{country}-{trade}-{4 random digits}` (e.g. `AE-EL-4821`), unique, never a
  counter.
- Organization permission checks share `OrganizationPermissions::allows()`.

### D-055 · Organization review
- `/admin/organizations` lists open registrations oldest first for staff with
  `verifications.review` (superuser, verification officer). Staff open evidence through signed
  links, then approve, ask for more information or reject. Asking or rejecting needs a reason code;
  an optional message goes to the organization. Owners get an email for every decision.
- Approval verifies the organization. Asking for more keeps it pending; when the organization adds a
  document the review goes back to the reviewer. A rejected organization can send a new review
  request after fixing its documents.
- The reviewer's message is stored in `verification_requests.decision_note`, separate from `notes`
  (intake flags such as free webmail).

### D-056 · Post a Job wizard and job lifecycle for employers
- Four steps (requirement; skills and languages; employment terms; benefits, permit/visa payers,
  deadline and itemised costs) then preview and submit. Each step saves; the first creates a draft
  with its public code. Amounts are entered in whole units and stored as `amount_minor`.
- Submitting needs a verified organization, the destination's mandatory disclosures (country rule
  keys mapped to job fields, e.g. AE: salary, accommodation, hours, contract length) and only
  allowed worker-paid costs. A worker-paid visa or work permit counts as a worker cost.
- A change to any `JobPosting::MATERIAL_FIELDS` or to the costs of a published or paused job
  resubmits it for review straight away (it leaves public search until approved); the review notes
  list the changed fields. Title, headcount, skills, languages, joining window and deadline are not
  material. A material change that would break the cost rules is refused.
- Employers pause, resume and close live jobs, and archive drafts and closed jobs, following the
  job state machine. Viewers and hiring managers see jobs but cannot post. Jobs are only reached
  through the current organization.
- The organization dashboard shows live, in-review, needs-changes and draft counts and the latest
  jobs.

### D-057 · Job review
- `/admin/jobs` lists submitted jobs oldest first for staff with `jobs.moderate` (superuser,
  verification officer, trust officer). Each shows the Truth Card exactly as workers will see it,
  the employer's verification, why a live job came back, and the corridor-rule checks.
- Staff publish, ask for changes (`more_information_requested`) or reject, with the same reason
  codes and message flow as organization review. Publishing re-checks the employer and the cost
  rules and is refused if either fails. The first publication sets `published_at`; republishing
  after a change keeps it. Employer owners are emailed each decision.

### D-058 · Find Jobs, Truth Card page and matching
- `/jobs` lists published jobs whose deadline has not passed, 20 per page, newest first or
  closing soonest. Search matches title, city, job code, employer, trade and skill names
  (English taxonomy names for now). Filters: destination, trade, "no costs for the worker"
  (no worker-paid cost item, visa or permit), "accommodation provided", "salary shown".
- `/jobs/{code}` shows the Truth Card. Jobs that are not published are visible only to their
  organization and to job moderators; everyone else gets 403.
- Guests and organization users see the public pages; workers see the same jobs inside their
  workspace with "X of Y requirements met": trade, minimum experience, each required skill, each
  language and a ready passport, each listed as met or not. No score or ranking.
- "I can do this job" stays disabled until Module 4; guests get "Join free to apply".
- "Report this job" needs a signed-in user and creates an open `Report` (fake job, unauthorised
  payment, misleading pay or conditions, impersonation, other with a description) for Module 8.

### D-059 · Module 3 sample data
- Gulf Star Facilities (`owner@gulfstar.test`) is a pending employer with an open registration
  review, so the organization queue is never empty locally.
- ABC Contracting has draft job AE-ST-0042 (Steel Fixer) whose recruitment fee is charged to
  workers; it shows the corridor-rule block in the wizard and cannot be submitted until fixed.
- The existing SA-HV-0021 and PL-WH-0014 reviews populate the job review queue.

## Module 4 — Applications

### D-060 · Module 4 inputs (owner answers, 2026-10-21)
- **Pipeline per job.** Recruitment projects that group several jobs with a target headcount come
  later with analytics (Module 9).
- **Talent search shows the worker's full name** with trade, experience, countries, skills,
  languages, passport readiness, availability, evidence states and SkillShow. Never the phone
  number, documents or address (product rule 6).
- **Worker alerts are in-app only** until Module 5: the application tracker shows status, next
  step, interviews, offers and job-change notices. Stage events recorded now feed WhatsApp, SMS and
  email in Module 5.
- **Review cadence:** once at the end of the module.

### D-061 · Hiring schema
- `applications` gains the assigned recruiter (shown to the worker as "Handled by"), the SkillShow
  shared with it, `stage_changed_at`, and `job_change_seen_at` (product rule 5 notice).
- `application_consents` gains `granted_at`, `revoked_at` and the exact `document_id` shared for
  document items, so a replaced passport scan is never shared silently. Revoking keeps the row.
- `application_stage_events` is the application timeline (from, to, actor, reason code, note);
  rows are never edited. The activity log still records each state change.
- `interviews` (time, length, in person / video / phone, place or link, instructions, outcome) and
  `offers` (pay, start date, answer-by date, note, status `sent/accepted/declined/withdrawn`). Offer
  status is a categorical enum like D-014; the application state machine carries the stage.
- `shortlists` (named per organization, optionally for a job) and `shortlist_entries` (one per
  worker per list, with a note). Being on a list gives no extra access to the profile.
- `RejectionReason` codes: requirements not met, not enough experience, skills not shown,
  interview not passed, trade assessment not passed, positions filled, not available, other.

### D-062 · Applying and identity documents
- "I can do this job" opens `/jobs/{code}/apply` for workers when the job is published and its
  deadline has not passed; a worker who already applied goes to that application instead. Guests
  still get "Join free to apply". Applying needs a trade on the Skill Passport, nothing more.
- Shareable items: Skill Passport (always, cannot be switched off), SkillShow (only an approved
  one), work experience and training certificates (on by default when the worker has any),
  passport scan and phone number (off by default, flagged sensitive; the scan only once uploaded).
  Items the worker has nothing for are ignored even if posted.
- `CreateApplication` stores, in one transaction: the application, a `ProfileSnapshotData` copy
  (identity, trade, years, passport readiness, availability, skills, languages, destinations, the
  "X of Y requirements met" at that moment, plus work history and certificates only when shared),
  one `application_consents` row per item (granted or not), an `application_share` consent and,
  for a shared scan, a `document_share` consent naming the exact document. Snapshot names are
  captured in English. Phone numbers and files are never copied into the snapshot; they are read
  live while the consent is active.
- Duplicate applications are refused with a message, also when two requests race (unique index).
- `/passport/documents` holds one passport and one national ID scan (PDF, JPG or PNG, ≤ 10 MB,
  private disk) as `uploaded_unreviewed` evidence (Module 8 verifies). A new upload of the same
  type replaces the old one; replacing or deleting a scan revokes every application share of it.
  Only the passport scan is an application item; national IDs are for mobility cases (Module 6).

### D-063 · Worker application tracker
- `/applications` lists the worker's applications with status, next step, the next interview and a
  "job changed" flag. `/applications/{id}` shows the stage list, who handles it (assigned
  recruiter and organization, else the organization), next step, interviews, offers and what is
  shared.
- Stage list order: applied, reviewed, qualified, shortlisted, interview, trade assessment,
  selected, offer, accepted. Stages the organization moved past show as skipped; a rejected,
  withdrawn or declined application stops at the stage it reached, marked as blocked. Workers see
  the rejection reason code, never internal notes or the person who moved the stage.
- Workers withdraw (any open offer is withdrawn with it), accept or decline the open offer, and
  stop sharing any item except the Skill Passport. Revoking keeps the consent row with its time.
  Accepting does not create anything yet; mobility cases arrive in Module 6 (product rule 8).
- Product rule 5: when a job has a material change after the worker applied, the application
  shows "This job changed on …" with a link to the Truth Card until the worker taps "I have read
  it" (`job_change_seen_at`).

### D-064 · Employer applicants and pipeline
- `/org/jobs/{code}/applicants` is the job's pipeline: stage counts (all stages plus rejected,
  withdrawn, declined), 50 applicants per page oldest first, each with trade, years, country,
  "X of Y requirements met" from the snapshot, SkillShow shared, stage, next interview and who
  handles it. A table on desktop, stacked cards on mobile. Members with `applications.manage`
  select applicants and move them together; ones that cannot make the move are skipped and
  counted in the message.
- Organizations move applicants only to reviewed, qualified, shortlisted, interview, trade
  assessment and selected. "Offer" happens by sending an offer; accepted, declined and withdrawn
  are the worker's. Rejecting needs a `RejectionReason` (shown to the worker); the optional note
  stays internal.
- `/org/applicants/{id}` shows the snapshot projection with work history and certificates removed
  once the worker stops sharing them, the approved SkillShow, the phone number and the passport
  scan only while shared, the stage list, history (with who moved it and internal notes),
  interviews, offers and the consent list. `SkillShowPolicy` and the private media route allow an
  organization member only through an application whose consent for that item is active.
- Any member can be assigned as "Handled by". Interviews (`interviews.manage`: owner, HR admin,
  recruiter, hiring manager, case officer) are entered in the scheduler's own time zone, stored in
  UTC, must be in the future, need an address in person; booking one moves the applicant to
  Interview. Outcomes (passed, not passed, did not attend, cancelled) never move the stage on their
  own (product rule 14).
- Offers use the job's currency and pay period, cannot be below the Truth Card salary, need a
  salary on the job, and only one can wait for an answer at a time. Rejecting or withdrawing
  withdraws an open offer.
- The organization dashboard adds applicants, new, shortlisted and upcoming interviews for members
  who can see applications; job pages link to the pipeline with the applicant count.

### D-065 · Talent search and shortlists
- `/org/talent` is for members with `talent.search` (owner, HR admin, recruiter; agency owner and
  case officer) of a verified organization; unverified organizations see a locked page that links
  to verification. It lists only discoverable workers: "employers and agencies" visibility, an
  active `discovery` consent, a trade, and an account that still exists.
- Filters (spec §8): text (name, headline, Passport number, English skill names), trade, minimum
  years, country of residence, countries worked, language, passport ready, available within 30
  days, has issuer-verified evidence, has a SkillShow. Results are ordered by last profile update,
  never by a score or video. Choosing one of the organization's jobs adds "X of Y requirements
  met" to every card without reordering.
- Cards and the profile page show name, trade, years, countries, skills, languages, passport
  readiness, availability, credential titles with their own evidence states, and a SkillShow only
  when it is approved and visible to employers and agencies. Never a phone number, document, photo
  or address. Organizations contact workers by inviting them to apply (messaging in Module 5).
- Applicants who did not choose to be found are not visible here, even to the organization they
  applied to: their live profile could show items they did not share. They are handled in the
  pipeline through their snapshot (product rule 6).
- Shortlists (`shortlists.manage`: owner, HR admin, recruiter, hiring manager, agency owner, case
  officer) are named per organization, optionally for one of its jobs, with a team note per
  worker. Only discoverable workers can be added; if a worker later hides their profile the entry
  stays without any details. Deleting a list is not told to workers.

### D-066 · Module 4 sample data and review
- Sample applications are built with the real `ProfileSnapshotData`, per-item consents, stage
  history, interviews and offers, so every seeded application opens in the pipeline.
- ABC Contracting's own live job AE-MT-0115 (Maintenance Electrician, Dubai) has applicants at
  every stage: Sunil applied (phone shared), Nimal reviewed, Rahim shortlisted (passport scan and
  phone shared, sees the "job changed" notice), Arjun with an upcoming video interview, Hasan with
  an open offer handled by Sara, Tek rejected (requirements not met). ABC also has the shortlist
  "Dubai towers — electricians" with Karim and Ravi.
- Rahim's passport document has a placeholder PDF. The seeder sets the media UUID itself because
  seeding runs without model events.
- Visual review: employer 96/96 and worker 60/60 clean (en, bn, ur; light, dark; 390, 1440). Review
  fixes: employer-side wording for shared items and the match note; lighter talent card
  placeholder.

## Module 5 — Messaging and notifications

### D-067 · Module 5 inputs (owner answers, 2026-10-21)
- **Laravel Reverb now** for live messages and notification badges (private channels per
  conversation and per user), with `laravel-echo` and `pusher-js` on the frontend. Reverb runs as
  its own Sail service locally and as a separate process on staging and production.
- **One generic WhatsApp template** ("You have an update on your BLUESKILLS application for
  {job}. Open the app: {link}") in en, bn, hi, ur and ne; details stay in the app.
- **Twilio for SMS**, called over its HTTP API (no SDK package). The owner supplies the account
  SID, auth token and sender per country; locally SMS stays on the `log` driver.
- **Review cadence:** once at the end of the module.

### D-068 · Messaging schema and Reverb setup
- `conversations` (one per application, between the worker and the posting organization),
  `messages` (never edited; worker, organization or system side; private attachments),
  `conversation_reads` (last read per user, for unread counts), Laravel `notifications` with ULID
  notifiables, `notification_preferences` (per user, event and channel; no row = on),
  `outbound_messages` (every WhatsApp, SMS and email attempt with status, provider id, attempts and
  `send_after` for quiet hours; recipient encrypted) and `job_invitations` (one per job and
  worker; sent, applied, declined, expired; at most 50 per organization per day).
- Conversations become read-only 30 days after the application closes. Organization members with
  `applications.view` read; members with `applications.manage` or `interviews.manage` write.
- Notification events: workers (shortlisted, interview booked or changed, offer received,
  application closed, job changed, invitation to apply, new message) use WhatsApp then SMS;
  organization members (new applicants daily, worker accepted/declined/withdrew, new message) use
  email. In-app notifications are always on.
- Reverb 1.12 is the newest stable release and needs `guzzlehttp/psr7` 2.x, so Guzzle went from
  8.3 to 7.16 (Laravel 13 supports both; our HTTP calls use Laravel's client). Sail runs Reverb as
  the `reverb` service (host port 8081); the app publishes to `reverb:8080` inside Docker and the
  browser connects to `localhost:8081`. Tests broadcast to the `null` driver.

### D-069 · Conversations
- A conversation is created the first time either side opens it from the application ("Message
  the employer") or the applicant page ("Message the worker"). Workers find theirs under
  Messages; organizations have an inbox per workspace, filterable by job.
- A conversation becomes read-only 30 days after the application was rejected, withdrawn or
  declined. An accepted offer keeps it open, because recruitment and mobility continue.
- Messages are text up to 2,000 characters with up to 3 attachments (PDF, JPG, PNG, ≤ 10 MB) on
  the private disk, served only to the conversation's parties. Workers see organization messages as
  "member · organization"; the worker's phone number is never part of a conversation.
- Every thread shows a fixed line that BLUESKILLS and genuine employers never ask for money, and
  each message from the other side can be reported (someone asked for money, harassment,
  impersonation, something else) as a Trust report on the message for Module 8. Nothing is
  scanned or blocked automatically (product rule 14). `reports.description` is required, so an
  empty description is stored as an empty string.
- Unread state is per person (`conversation_reads`); message and read times keep microseconds so
  a reply in the same second as a read still counts. Opening a thread marks it read.
- Live updates: `MessageSent` broadcasts ids only on `conversations.{id}`,
  `organizations.{id}.inbox` and `users.{id}`; open threads and the navigation badges reload
  their own props. Channel access reuses the conversation policy.

### D-070 · In-app notifications
- One queued `AppNotification` (after commit) stores the event, its parameters and an app path;
  titles and bodies are rendered when shown, in the reader's language, with times in the
  reader's time zone and reason codes in words. Notifications broadcast (ids only) on
  `users.{id}` so the bell updates live.
- Workers are told when they are shortlisted, an interview is booked (with its local time), an
  offer arrives, the application is closed (with the reason), a job they are still in changes
  materially (product rule 5), and when the organization writes. Moving an applicant to Interview
  without booking one, and other stage moves, do not alert.
- Organization alerts go to the application's assigned recruiter, or else to every member with
  `applications.manage`: the worker accepted, declined or withdrew, or wrote a message.
  `notifications:new-applicants` runs daily at 06:00 UTC and sends one alert per job with new
  applicants in the last 24 hours to members who can see applicants.
- `/notifications` lists them for every user type; opening one marks it read and follows its
  link (app paths only, never off-site). "Mark all as read" clears the bell.

### D-071 · WhatsApp, SMS and email delivery
- `AppNotification` also goes through `ExternalChannel`. Worker events: WhatsApp if on for the
  event, else SMS if on; organization events: email if on and the member has an address. No row
  in `notification_preferences` means on. `/notifications/preferences` lets each person switch
  channels per event for their own audience; in-app stays on.
- Every attempt is an `outbound_messages` row (recipient and payload encrypted). Delivery runs in
  `DeliverOutboundMessage` (queued, one try): text is rendered in the recipient's language and
  time zone; a failure is recorded and a failed WhatsApp alert falls back to SMS when SMS is on
  for the event. No automatic retries, so an outage never floods a worker later.
- Quiet hours: worker WhatsApp/SMS between 22:00 and 07:00 local time is stored as `held` with
  `send_after` 07:00 local; `notifications:release-held` runs every five minutes. Email is not
  held.
- Drivers: `NOTIFY_WHATSAPP_DRIVER` = `log` | `cloud` (Cloud API, template messages only);
  `NOTIFY_SMS_DRIVER` = `log` | `twilio` (REST API with basic auth). Twilio picks the sender per
  destination country from `TWILIO_SENDERS` (JSON), then `TWILIO_MESSAGING_SERVICE_SID`, then
  `TWILIO_FROM`. The log drivers refuse to run in production.
- WhatsApp template to submit to Meta (category Utility), name `blueskills_update`, two body
  parameters, in en, bn, hi, ur and ne (other languages fall back to English; Meta's support for
  `ne` must be confirmed when submitting):
  en: "You have an update on your BLUESKILLS application for {{1}}. Open the app to see it: {{2}}"
- SMS text: "BLUESKILLS: {title}. Open: {link}" in the worker's language.

### D-072 · Invitations to apply
- From a talent profile, members who may search talent invite a discoverable worker to one of
  the organization's jobs that is open for applications, with an optional note (≤ 300
  characters). One invitation per job and worker; at most 50 per organization in 24 hours. The
  worker gets a `job_invitation` alert.
- Workers see open invitations at the top of My applications with the job, the organization, the
  note and a reminder that an invitation is not an offer and never needs payment. They read the
  Truth Card and apply through the normal flow (which marks the invitation applied), or tap "Not
  interested". Invitations to jobs that stopped taking applications show as expired.
- No free conversation starts from an invitation; it opens once the worker applies (D-069).

### D-073 · Module 5 sample data and review
- Sample conversations: Rahim and Northstar about the medical test (from the prototype, without
  its line about the agency keeping the passport), and on ABC's AE-MT-0115 with Hasan (unread for
  Sara), Arjun and Rahim (unread for Rahim). ABC invited Karim to AE-MT-0115. Rahim, Karim, Sara
  and the ABC owner have sample notifications; one sample WhatsApp delivery is logged.
- Review fixes: conversations stay open after an accepted offer; the daily digest pluralizes.
- Visual review: employer 60/60 and worker 48/48 clean (en, bn, ur; light, dark; 390, 1440).

## Module 6 — Agency and mobility

### D-074 · Module 6 inputs (owner answers, 2026-10-21)
- **Mandates are confirmed by the employer and checked by staff.** The employer must be a
  verified organization on BLUESKILLS; its Owner or HR Admin confirms the agency's mandate in the
  app, then staff check the mandate letter and the agency licence. No agency-created employers.
- **No passport retention.** The UAE stage "Passport received by agency" becomes "Passport
  submitted for visa stamping", with a fixed note that the passport goes back to the worker right
  after stamping and that retention can be reported. Templates are updated everywhere.
- **Staff review of licences and mandates now**, minimal, at `/admin/mandates`, following the
  organization and job review pattern. Module 8 adds the wider evidence queues.
- Employer Owners, HR Admins and Recruiters get `mobility.manage` for cases from their own direct
  jobs; for agency jobs the agency manages the case and the employer can view it.
- **Review cadence:** once at the end of the module.

### D-075 · Agency and mobility schema
- `organizations.licence_countries` lists the destinations an agency licence covers. A licence
  is valid when the agency is verified, `licence_expires_on` is in the future and, for a job or
  mandate, the destination is listed.
- `job_mandates` gains its scope (trades as `occupation_ids`, `destination_country_code`), who
  requested it, and the employer's answer (who, when, note). Status flow: `awaiting_employer` →
  `pending` (staff review) → `verified` | `rejected`; `declined` when the employer says no;
  `expired` / `revoked` later. A mandate backs a job only while verified, in date and licensed
  for the destination. The mandate letter is evidence on the mandate's verification request.
- `mobility_cases` gains a reference, the managing, employer and agency organizations, the
  handler ("Handled by"), and a closing reason (joined, worker withdrew, employer cancelled, visa
  refused, medical test not passed, other). Stages gain the worker's next action and a blocked
  reason. `mobility_case_events` is the case history (never edited).
- `case_documents`: one checklist item per document (type, title, requested / uploaded /
  accepted / returned, provided by the worker or the organization, the file, whether the
  employer may view it, shared / revoked / decided times, due date). A worker's file is visible to
  the organizations only while shared and not revoked (product rule 6).
- New permission `mandates.confirm` for employer Owners and HR Admins. `mobility.manage` now comes
  with every hiring role (Owner, HR Admin, Recruiter, Agency Owner, Case Officer); a Compliance
  Reviewer can view cases but not run them. Re-run `RolesAndPermissionsSeeder` on deploy.
- The UAE template stage `passport_received` became `passport_submitted` ("Passport submitted for
  visa stamping"); re-run `CountryRuleSeeder` on deploy.

### D-076 · Agency licence page and mandates
- Agencies see their licence at `/org/licence` (number, expiry, verified, valid) and set the
  destinations it covers themselves; staff check those against the licence on each mandate review.
  Changes are written to the activity log.
- An agency requests a mandate from a **verified employer on BLUESKILLS** with the trades,
  destination, headcount, validity dates and the signed letter. The request is refused unless the
  licence is valid for that destination. References look like `MD-2026-4821` (unique per agency).
- The employer's Owners and HR Admins (`mandates.confirm`) confirm or decline in the app; a
  decline needs a note. Confirmed mandates join the staff queue at `/admin/mandates`, declined ones
  stop there.
- Staff approve, reject or ask for more information with mandate reason codes (letter not signed,
  scope mismatch, licence expired / out of scope, unreadable, details mismatch). Approval re-checks
  the licence. "Needs info" keeps the mandate pending; new evidence from the agency puts the review
  back in review.
- The mandate letter is visible only to staff reviewers, the agency and the named employer.
- Notifications: `mandate_requested` (employer), `mandate_answered` and `mandate_reviewed` (agency).

### D-077 · Agency job posting (product rule 10)
- Agencies post through the same wizard as employers. The first step asks for one of the
  agency's mandates; the job's employer is the mandate's employer and the agency is shown as the
  recruiter on the Truth Card. Employers cannot attach a mandate to their own jobs.
- An agency may start a job only while it holds a **usable** mandate: verified, in date, and under
  a licence valid for the mandate's destination. Otherwise the jobs page explains how to get one.
  Agency Owners and Case Officers post; Compliance Reviewers do not.
- The job must stay inside the mandate: one of its trades, its destination, and no more workers
  than the places left. Places left = mandate headcount minus the headcount of the agency's other
  jobs on that mandate that aren't closed, archived or rejected.
- The same check runs when the requirement step is saved, at the wizard preview, on submission,
  on a live job's material change and at staff approval, so a mandate that lapses, is revoked or
  loses its licence cover blocks the job from going (back) live. Live jobs whose mandate lapses
  are not paused automatically yet; that comes with the agency workspace (6f/6g).
- Sample data: Northstar's licence covers AE and SA; MD-1107 covers electricians to AE with 300
  places, MD-1121 HVAC technicians to SA.

### D-078 · Mobility cases
- **Opening:** accepting an offer opens the case in the same transaction (product rule 8). The
  case is a separate record with a reference like `MC-AE-2026-0188`. The organization that posted
  the job runs it (the agency for agency jobs; the employer for direct jobs), and "Handled by" is
  the application's assignee, otherwise whoever sent the offer. Declined offers open nothing.
- **Stages** come from the destination's `mobility_stage_template` country rule. A template stage
  may set `done_on_open` (UAE "Job selected"); the first other stage starts at once. A destination
  without a template uses a short generic one (contract accepted, medical test, visa, travel,
  joined) so an accepted offer always opens a case. Re-run `CountryRuleSeeder` on deploy.
- **Running a case:** only the current stage changes, in template order. The managing
  organization (members with `mobility.manage`) can mark it done, skip it or put it on hold (both
  need a reason the worker sees), resume it, and set the worker's next action and due date.
  Finishing the last stage closes the case as joined. Closing early takes a reason (worker
  withdrew, employer cancelled, visa refused, medical test not passed, other with a note) and
  cancels the case; closing as joined needs every stage done or skipped. Closed cases can't reopen.
- **Who sees what:** the worker sees their case, with the organization's name rather than the
  staff member's in the history. The employer of an agency job and agency Compliance Reviewers can
  follow a case but not change it. Other organizations get a 404.
- Every change writes a case event (never edited) and an activity-log entry with before/after
  values. Workers get `case_opened`, `case_updated` (stage done, skipped, on hold, resumed, new
  next action) and `case_closed` alerts on their usual channels.
- Stage names are translated with `mobility.stage.{key}` and fall back to the template's English
  label. The UAE passport stage shows the fixed note that the passport goes back to the worker
  right after stamping (D-074).
- Case lists for each workspace and the documents checklist follow in 6e and 6f.

### D-079 · Case documents
- **Checklist:** opening a case adds one item per document in the destination's `document_types`
  country rule (a passport copy and a signed contract where there is no rule). The contract, work
  permit and visa come from the managing organization; everything else from the worker. The
  managing organization can add items (type, name, who provides it, due date, instructions);
  organization registration documents can't be asked for.
- **Per-item sharing (product rule 6):** the worker shares a file for one item at a time, by
  uploading it or, for a passport or ID, by sharing the scan on their Documents page (a passport
  uploaded on the case becomes that scan). Sharing is recorded with its time and the worker can
  stop it whenever the item isn't accepted yet; the organizations lose the file at once and the
  item is needed again. Deleting a scan on the Documents page also stops every case share of it.
  A replaced case-only file is deleted.
- **Who sees what:** the worker sees every item and file on their case. The managing organization
  sees the whole checklist and a worker's file only while it is shared. The employer of an agency
  job sees only items marked for it: the worker decides for their files (passport and ID default
  to "agency only", as in the prototype; others default to visible), the agency for its own. Files
  go through the private-media route, which checks this again.
- **Checks:** the managing organization accepts a shared file or sends it back with a reason code
  (hard to read, not the document asked for, expired, pages missing, details mismatch, other with a
  note). Accepted items can't be replaced or revoked from the case page.
- **Contract:** the organization uploads it; the worker confirms it or sends it back with what needs
  changing, and the organization uploads a corrected one. Confirming records who and when; the
  organization still moves the contract stage itself, so no stage changes without a person doing it.
  Other organization files (permit, visa) are ready as soon as they are uploaded.
- Every change writes a case event (now linked to its checklist item) and an activity-log entry
  with before/after values. Alerts: `case_document` to the worker (needed, added, contract ready,
  accepted, sent back) and `case_document_answered` to the case handler, else everyone at the
  managing organization with `mobility.manage` (shared, stopped sharing, contract confirmed or
  queried).

### D-080 · Agency workspace and mobility lists
- **Mobility** (`/org/cases`, agencies and employers): every case the organization runs or
  employs on, open by default, with closed / all, a search by worker name or case number, and
  shortcuts to what needs attention: shared documents waiting for a check, current stages past
  their due date, and stages on hold. Each row shows the current stage, a progress bar, the due
  date and who runs the case; cases an employer only follows are marked as such. Employers get the
  same list in their navigation.
- **Job orders** for agencies is the jobs list; agency jobs show the employer and the mandate.
- **Candidates** (`/org/candidates`): applicants across all of the organization's jobs, from their
  application snapshots, filtered by job, stage and name or Skill Passport number, with a link to
  the mobility case once one is open. Same projection as the per-job pipeline (no phone numbers or
  documents).
- **Compliance** (`/org/compliance`, Agency Owner and Compliance Reviewer): licence validity (warns
  60 days before expiry), each verified, expired or revoked mandate with places used/left, live
  jobs and a 30-day end warning, job orders without a usable mandate, and case attention counts.
  The agency dashboard warns about the licence and shows mobility tiles; navigation hides links a
  member can't open (shared `organizationAbilities`).
- **Lapsed mandates (completes D-077):** a daily job (`jobs:pause-lapsed-mandates`, 00:30 Gulf
  time) marks verified mandates past their end date as expired and pauses live agency jobs whose
  mandate can no longer back them (expired, revoked, licence lapsed or not covering the
  destination). Only the mandate's validity triggers a pause; a headcount overrun is still caught
  at the next save or review. The agency's members who manage jobs get `job_paused_mandate`; the
  change is in the activity log with the reason. Resuming an agency job runs the same mandate
  check, so it stays paused until a usable mandate backs it.

### D-081 · Module 6 sample data and review fixes
- **Sample data:** Northstar's expired mandate MD-1088 with the job it backed (UAE-EL-0004,
  paused, the agency owner alerted); Ravi's visa stage on hold for the embassy appointment; a police
  clearance Northstar asked Karim for, shared and waiting for a check (Imran alerted). With Rahim's
  medical due, every Mobility shortcut and the compliance page have something to show.
- **Review fixes:**
  - Case Officers no longer see Mandates and Licence in the menu or a link on the licence warning;
    those pages stay with Agency Owners and Compliance Reviewers (D-076).
  - The Truth Card shows "Job reviewed" for paused and closed jobs too: they passed review before
    going live, and a material change sends a job back to review with its own status.
  - An employer following an agency case doesn't see history lines about documents not shared
    with it, and "documents to check" counts only the cases an organization runs, since only the
    managing organization checks files.
- **Deploy notes for Module 6:** run migrations; re-run `RolesAndPermissionsSeeder` (D-075) and
  `CountryRuleSeeder` (D-075, D-078); make sure the scheduler runs (`jobs:pause-lapsed-mandates`).

### D-082 · Module 7 inputs (owner answers, 2026-10-21)
- **Invitations:** the institute shares the cohort link, QR code or WhatsApp text itself;
  BLUESKILLS sends nothing to people who haven't joined. An optional roster (names and phones)
  only tracks who accepted; a rostered phone is matched only once that worker accepts.
- **Job-progress sharing** with an institute is off by default. The graduate chooses it when
  accepting and can change it in Privacy at any time.
- **Pools:** an institute groups accepted graduates into pools and may publish a pool to verified
  employers and agencies, who see only members visible to employers and reach them through
  "invite to apply". Employer and agency shortlists serve as their own talent pools.
- **Attestations** can be withdrawn by the issuing institute with a reason; the evidence shows as
  rejected and the worker is told. Workers can hide but not edit them.
- **Review cadence:** once at the end of the module.

### D-083 · Institute schema
- `courses` (title, trade, length, description) and `cohorts` (course, name, dates, unique
  8-character invite code without look-alike characters, open / closed).
- `cohort_members`: one row per graduate link. The roster name and phone are optional and only
  for tracking; the worker is set only when they accept. Status invited → accepted | declined;
  accepted → left. `shares_progress` (off by default) lets the institute follow applications.
- `training_attestations` and `trade_assessments` (pass / not passed, optional score 0–100):
  issued or withdrawn with who, when and why. Each is the subject of its own evidence item on the
  worker's profile; the course title is copied so later course edits don't change an attestation.
- `talent_pools` and `talent_pool_members`; a pool is visible to employers and agencies only
  while `published_at` is set.
- `evidence_items.hidden_at`: the worker hides an issuer's item from their Skill Passport.
- Who may do what: every institute role sees courses and cohorts; Owners and Trainers manage
  courses; `cohorts.manage` edits cohorts; `graduates.invite` shares links and keeps the roster;
  Owners, Trainers and Assessors attest; Owners and Assessors record assessments (both only for
  accepted graduates of a verified institute); Owners and Placement Officers follow progress of
  graduates who share it and keep pools. Verified employers and agencies who may search talent
  browse published pools.

### D-084 · Courses, cohorts and graduate invitations
- Institutes add courses (title, trade, length, description) and cohorts (name, dates). Each
  cohort has an invite link `/i/{code}`, a QR code (shown on the page and downloadable as SVG for
  posters; BaconQrCode, already installed with Fortify, is now declared in composer.json) and an
  editable WhatsApp message the institute copies and sends itself. Closing a cohort stops new
  graduates joining; those who joined stay.
- **Roster:** pasted "name, phone" lines (phones read as the institute's country, up to 300 a
  time, duplicates skipped) only track who joined. A line is matched to a worker when that worker
  accepts, never before; a decline marks the line declined without naming the profile. Lines
  nobody answered can be removed; graduates who joined can only leave themselves.
- **Invite page:** anyone can open it; it names the institute (with "accreditation checked" when
  verified), the course and cohort, and lists what the institute sees (name, Skill Passport number,
  SkillShow, what it attests) and never sees (profile edits, documents, phone). A signed-in worker
  accepts, optionally ticking "let the institute follow my applications" (off by default), or
  declines. Guests join with their phone; existing workers return to the page after signing in
  and new workers find a banner on their home page until they answer.
- **My institutes** on the Privacy page lists institutes the worker joined, with the sharing
  switch and "Leave this institute", which also takes them out of that institute's pools.
- Institute members with `graduates.invite` get `graduate_joined`. Every change is in the
  activity log.

### D-085 · Training attestations and trade assessments
- Owners, Trainers and Assessors attest that a graduate completed the course (date, optional note);
  Owners and Assessors record trade assessments (test, pass / not passed, optional score 0–100,
  date). Only for graduates who accepted, and only once the institute's accreditation is verified.
  One attestation per cohort place; a withdrawn one can be issued again.
- An attestation becomes a training evidence item and a **pass** a trade-assessment item, each
  "issuer verified", naming the institute and its date, with the course title copied at issue.
  A result that isn't a pass stays with the institute and the graduate only, so it never counts
  against them with employers. The worker's own experience and credentials are never touched.
- The issuing institute can withdraw either with a reason: the item becomes rejected with
  `withdrawn_by_issuer` (evidence may now go issuer verified → rejected) and the worker is told.
- Workers can hide an institute item from employers and agencies (it stays on their own
  Skill Passport, marked hidden) but can't delete it. Hidden items are left out of talent
  profiles, application snapshots, the "verified evidence" talent filter and its count. Talent
  profiles and shared credentials now include passed assessments with their issuer.
- Alerts: `institute_record` to the worker (attested, passed, not passed, withdrawn).

### D-086 · Talent pools, employer interest and placements
- Institute Owners and Placement Officers (`talent_pools.manage`) create pools (name, trade,
  short description) and add graduates who accepted one of the institute's cohorts; anyone else
  is refused. Leaving the institute takes a graduate out of its pools (D-084).
- **Publishing** needs a verified institute. Verified employers and agencies whose member may
  search talent see published pools of verified institutes under "Institute pools", with only the
  graduates who chose to be found by employers (the talent-search rule), shown as talent cards
  that open the usual talent profile and "invite to apply". Unpublished pools are refused. The
  institute's pool page says how many of its graduates employers can actually see.
- **Placements** (`placements.view`: Owners and Placement Officers) lists only graduates who
  share their progress: invitations to apply from employers and agencies ("employer interest"),
  their applications with status, and the mobility case status, counting a completed case as
  joined. Graduates who don't share progress aren't listed at all.
- Navigation shows Courses, Cohorts, Talent pools and Placements to the institute roles that can
  open them, and "Institute pools" to employer and agency members who may browse.

### D-087 · Institute workspace, sample data and review fixes
- **Institute workspace:** the dashboard shows graduates joined (with roster lines waiting),
  graduates with a SkillShow, training attested and assessments passed, plus the latest cohorts.
  "Graduates" lists everyone across cohorts (joined / roster waiting / all, by cohort) with the
  same attest, assess and withdraw controls as a cohort page (prototype i-grads). The separate
  "Assessments" menu item was dropped: assessments live on the graduates table.
- **Sample data:** Meghna's Electrical Installation course and 2026 Cohort (invite link
  `/i/MTTCEW26`). Rahim and Karim joined and were attested; Rahim passed the practical test (82)
  and shares his progress; two roster lines wait and one declined. The published pool
  "Electricians ready to travel" holds both, so ABC Contracting sees it under Institute pools.
- **Review fix:** organization pages subscribed every member to the organization inbox channel,
  which members without applicant access (all institute roles) are refused; they now subscribe
  only when they may view applicants.

### D-088 · Module 8 inputs (owner answers, 2026-10-21)
- **Suspension:** a suspended organization's members can still sign in, read-only, to see their
  data and appeal; they can't post, publish, change jobs or message. Its live jobs are suspended
  and their applicants told. Existing applications and mobility cases continue. A suspended worker
  is hidden from talent search and pools and can't apply, but can sign in and appeal.
- **Reporters** get a short outcome only ("reviewed, action taken" or "reviewed, no action
  needed"), never details about the other party.
- **Corridor legal review** is recorded per country. A destination without it shows a staff-only
  "legal review pending" flag; publishing isn't blocked.
- **Sensitive files** (passport and ID scans) are opened only by Verification Officers and
  Superusers while reviewing, and every view is written to the audit log.
- **Review cadence:** once at the end of the module.

### D-089 · Trust schema
- `trust_cases`: reference `TC-2026-0001`, subject (organization, job or worker profile),
  category (the report categories), priority (low / normal / high / urgent), status open →
  investigating → action taken → closed (a closed case can be reopened), assignee, opener, summary
  and outcome reason code.
- `reports` gain the case they belong to, the outcome the reporter is told (action taken / no
  action), a reason code and who decided when.
- `trust_case_notes`: evidence notes with files on the private disk; `trust_actions`: suspend /
  restore with a reason code, append-only; `appeals`: statement, submitted → upheld | overturned,
  decided by another trust officer than the one who suspended.
- `worker_profiles.suspended_at` hides a worker from talent search and pools; `countries` record
  the corridor legal review (who, when).
- Reason codes (`TrustReason`) cover action (fake job, unauthorized payment, misleading
  conditions, impersonation, harassment, fake credentials, repeated breaches), no action (no
  evidence, not a breach, duplicate, resolved) and appeal outcomes.
- Trust Officers and Superusers (`trust.manage`) run cases.

### D-090 · Worker evidence review
- `/admin/evidence` (Verification Officers and Superusers) lists the documents workers uploaded
  (passport and ID scans, certificates, training records, work references) that are uploaded /
  unreviewed or verification pending, oldest first. Institute attestations and assessments are
  issued already and never appear here.
- "Start review" marks the item verification pending and assigns it to the officer; only then
  does the file open, through the private-media route, and every view is written to the audit
  log (`document.viewed_by_staff`, D-088). The officer verifies (issuer verified) or rejects with a
  reason code (unreadable, expired, details mismatch, not the document described, altered or not
  genuine) and an optional note; "more information" isn't offered: a rejection with its reason
  tells the worker what to upload again. The decision is a verification request like other staff
  reviews, so the history shows who decided what.
- Workers get `evidence_reviewed`. A daily task (`evidence:expire`) marks verified evidence expired
  once its document's expiry date passes (product rule 9).

### D-091 · Staff overview
- The staff Overview (prototype x-queue) shows a tile for each queue the viewer can work on, with
  what waits there: employer registrations, agency licences, institute accreditation, mandates,
  job Truth Cards, worker documents, SkillShows and trust reports. Tiles link to their queue.
  Staff without a queue permission (e.g. CRM agents) see no queue tiles.
- Marketplace numbers: workers registered, profiles complete, findable by employers, verified
  employers / agencies / institutes, live jobs, applications in the last 30 days, offers accepted,
  open mobility cases and workers joined.
- A staff-only notice counts destination corridors still waiting for legal review (D-088).

### D-092 · Trust reports, cases, suspensions and appeals
- **Reports** (`/admin/trust/reports`, Trust Officers and Superusers) show what was reported
  ("Report this job", "Report message"), who reported it and the message text. An officer
  dismisses one with a no-action reason (not enough evidence, not against the rules, duplicate,
  resolved), or opens a case. A report about something that already has an open case joins it.
  A reported message is about whoever sent it: the worker, or the organization.
- **Cases** (`/admin/trust/cases`) carry a priority, an assignee, evidence notes with files
  (screenshots, call logs, payment records; private, trust staff only) and actions. Opening a
  case assigns it to its opener.
- **Suspending** takes an action reason and a note, both kept in the case and the audit log:
  - a **job** must be live or paused; it leaves search and its applicants are told not to pay
    anyone;
  - an **organization** must be verified; its live and paused jobs are suspended with it, and its
    members can still sign in and read but every change in the workspace (jobs, hiring steps,
    messages, team, mandates) is refused with an explanation;
  - a **worker** is hidden from search and pools and can't apply.
  Applications and mobility cases already under way carry on. Lifting a suspension is a restore
  action; restoring an organization brings back the jobs suspended with it but not jobs that were
  suspended on their own. Owners are told both ways (`organization_suspended`,
  `account_suspended`, applicants `job_suspended`).
- **Closing** a case takes an outcome reason. Its reports close as "action taken" when the case
  suspended anything, otherwise "no action needed", and each reporter gets only that
  (`report_outcome`, in-app for organization reporters).
- **Appeals** (`/appeals`): a suspended worker, or an organization member who may change the
  organization or its jobs, sees what is suspended and why (no staff names or notes) and sends one
  appeal at a time. A trust officer other than the one who suspended upholds or lifts it with a
  note the appellant sees (`appeal_decided`); lifting adds a restore action.
- Workspace and worker home show a suspension banner linking to appeals.

### D-093 · Configuration and taxonomy
- **Configuration** (`/admin/config`, `config.manage`, Superusers): each country's currency,
  whether workers come from it and jobs go to it, and the corridor legal review (who marked it,
  when). Corridor rules are edited in structured form: who may recruit, the worker cost policy and
  the notice shown to workers (text); languages, the cost categories a worker may lawfully pay,
  the disclosures every job must make (the known disclosure keys only) and the documents a
  mobility case asks for (lists); and the mobility stages (ordered rows with "done when the case
  opens"). New stages get a key from their label; existing keys never change, and stage changes
  apply to cases opened afterwards. Language status (offered / in review / planned) is set here.
- **Taxonomy** (`/admin/taxonomy`, `taxonomy.manage`, Superusers): add and rename sectors,
  occupations, specializations and skills with a name per launch language (English required,
  others fall back to it). The slug is set from the English name once and never changes, so
  filters and links keep working. Only items nothing uses can be removed (workers, jobs, courses,
  pools, mandates, skills, SkillShows, job requirements); used ones are renamed instead.
- Every configuration and taxonomy change is written to the activity log with old and new
  values.

### D-094 · Audit log, sample data and review fixes
- **Audit log** (`/admin/audit`, Superusers): every activity-log entry, newest first, filtered by
  area (trust, verification, config, taxonomy, jobs…), person (name or email), record ID and
  date range, with the recorded before/after values. A freshly seeded database starts with an
  empty log because seeding writes records directly; real actions fill it.
- **Sample data:** a report on UAE-EL-0001 waiting (Sunil: "asked for 20,000 taka"); case
  TC-2026-0001 that suspended JP-IT-0007 over a misleading dormitory cost, with two reports, an
  evidence note and Global Facilities' appeal waiting; a dismissed duplicate report; the UAE
  corridor marked legally reviewed. Rahim's passport scan and work reference wait in the worker
  documents queue.
- **Review fix:** "was this job suspended on its own?" compared an enum with a string and was
  always false, so appeals pages missed suspended jobs and restoring an organization would have
  restored jobs suspended separately. Fixed with a regression test.
- **Deploy notes for Module 8:** run migrations; schedule `evidence:expire` (daily) alongside the
  existing tasks; mark each live corridor's legal review in Configuration.

### D-095 · Module 9 inputs (owner answers, 2026-10-22)
- **Billing:** invoices only. Finance Officers issue invoices and record bank-transfer payments by
  hand; credits, seats and plans are tracked. Limits are **not enforced** yet: a setting (off by
  default) will later make publishing a job use up a posting credit and limit seats and talent
  search. No payment provider (Stripe/Cashier) yet; billing goes through a provider interface so
  one can be added without hardwiring a vendor (spec §13). Pricing still needs approval before
  launch (spec §11).
- **Recruitment projects** are part of Module 9: group jobs, target headcount, source countries,
  team, stage counts, bulk shortlist/reject, deployment numbers (spec §6, deck slide 18).
- **CRM and phone numbers:** CRM agents see a lead's phone number on the lead page only (never in
  lists) and each view is written to the activity log. Workers can turn off "BLUESKILLS may contact
  me about my profile" under Privacy, which marks their lead do-not-contact.
- **Conversion stage** of a lead is derived from what the worker actually did (registered →
  profile complete → qualified → applied → selected → deployed), never set by hand. Follow-up
  status is set by the CRM agent.
- **Campaign visits** are stored as daily counts per campaign, not per-person tracking.
- **Review cadence:** once at the end of the module.

### D-096 · CRM, projects and billing schema
- New domain `app/Domain/Crm` (campaigns, daily visit counts, leads, contact attempts). Recruitment
  projects live in `Jobs`; plans, subscriptions, invoices and the credit ledger in `Billing`.
- `campaigns`: a code that never changes (used in `/c/{code}`), channel, source country, an
  optional partner institute or agency, landing (sign up or job search), status active ⇄ paused →
  ended. `campaign_visits` keep one count per campaign per day.
- `leads`: one per worker account (`user_id` unique) or hand-entered (name + phone). Source
  (campaign, institute invite, direct, walk-in, call centre, referral), assignee, follow-up status
  (enum, any agent-set value), next follow-up date, and a stored conversion stage (prospect →
  registered → profile complete → qualified → applied → selected → deployed) kept up to date from
  the worker's progress so lists can filter on it. `lead_contact_attempts` are append-only.
  Follow-up, assignee and stage changes go to the `crm` activity log.
- `worker_profiles.staff_contact_opted_out_at` records the Privacy opt-out.
- Projects are for **employers** (the deck puts Projects in the employer nav). A job belongs to at
  most one project (`jobs.recruitment_project_id`); the team is `recruitment_project_members`;
  references run per organization (`RP-0001`). Status active → completed | cancelled, completed
  can be reopened. New permission `projects.manage` for the hiring roles (Owner, HR Admin,
  Recruiter, Agency Owner, Case Officer); anyone with `jobs.view` sees projects. Deploy: re-run
  `RolesAndPermissionsSeeder`.
- `plans` are per organization type (agency and training fees set separately, spec §11) with
  price, period, posting credits per period, seats (null = unlimited), talent search and project
  access. `organization_subscriptions`: active → cancelled | ended.
- `invoices`: draft → issued (number `INV-2026-00001` given then) → paid, or void; amounts in
  minor units with VAT in basis points (5% UAE default, rounded half up), payment method and
  reference, and `provider` (`manual` until a provider exists). `invoice_lines` may carry credits
  added when the invoice is paid.
- `credit_ledger_entries` are append-only (updates and deletes throw); the balance is the sum.
- Billing settings in `config/blueskills.php` → `billing` (`BLUESKILLS_BILLING_ENFORCE`, off).
- Organization billing page: Owners / Agency Owners (`billing.manage`), not institutes yet.
  Analytics: anyone with `applications.view`.

### D-097 · Campaign links and lead capture
- **Campaign link** `/c/{code}` (case-insensitive, throttled): a running campaign (active, between
  its start and end dates) counts one visit per browser session per day and, for someone not
  signed in, remembers the campaign in the session. It sends people to sign-up (`/join`) or job
  search; signed-in users go to job search. Paused, ended or not-yet-started campaigns still
  redirect but count and attribute nothing; an unknown code goes home.
- **Attribution** is the last campaign link opened in the session; without one, an institute invite
  in the session (`/i/{code}`) credits that institute; otherwise "signed up directly".
- **Every worker who signs up becomes a lead** inside the registration transaction. A hand-entered
  lead with the same phone number is linked to the new account (keeping its original source)
  instead of creating a second lead.
- **Lead stage** moves forward only. It is refreshed on the queue (unique per worker, after
  commit) when a profile is completed, evidence or a SkillShow is checked, or an application or
  mobility case changes. Definitions (`LeadProgress`, shared with the campaign funnel): profile
  complete = passport completed; qualified = profile complete plus at least one issuer-verified
  evidence item or approved SkillShow; applied = any application; selected = an application
  reached Selected, Offer, Accepted or Declined; deployed = a mobility case closed as joined.
- **Privacy → "Calls from BLUESKILLS"**: turning it off marks the lead do-not-contact and clears
  the next follow-up; turning it back on returns the lead to "new". The worker's choice wins over
  an agent's. Both are written to the `crm` activity log.
- **Local Postgres** runs with `max_locks_per_transaction=256` (compose.yaml): eight parallel Pest
  workers each drop all tables at once and the default ran out of shared lock memory. CI runners
  use four workers; raise it there too if the error appears.

### D-098 · Worker CRM screens
- **`/admin/crm`** (CRM Agents, Superusers): every lead with views All / Due today (next
  follow-up today or earlier) / Mine / Unassigned (leaving out not-interested and do-not-contact)
  and filters for follow-up status, stage, source, campaign and name. Sorted by next follow-up,
  then newest. Lists never carry phone numbers.
- **Adding a lead** by hand: name, source country (the worker phone countries), mobile number
  (validated for that country, stored E.164), how we met them (walk-in, call centre, referral or a
  campaign) and "assign to me". A number that is already a lead or a worker account is refused
  with the existing lead's name.
- **Lead page:** phone number as a `tel:` link (each page view writes `lead.phone_viewed` to the
  audit log; no number and no log once the lead is do-not-contact), source, campaign or partner,
  Skill Passport number, the stage checklist and the contact history.
- **Logging a contact** (channel, result, note, next follow-up) sets the follow-up status from the
  result: reached → contacted, no answer / wrong number → no answer, asked to call back → call
  back, not interested → not interested (clears the next follow-up). The first agent to make
  contact is assigned if nobody was. Do-not-contact leads can't be logged as contacted.
- **Follow-up form:** assignee (staff with `crm.manage`), status and next follow-up date (today or
  later; cleared for not interested / do not contact). An opted-out worker's lead can't be moved
  off do-not-contact by an agent.

### D-099 · Campaigns and campaign analytics
- **`/admin/marketing`** (`crm.manage`): campaigns with channel, source country, optional verified
  partner institute or agency, where the link goes (sign-up or job search) and start/end dates.
  The link code comes from the name once (`FACEBOOK-BANGLADESH`) and never changes, so printed QR
  codes keep working. Campaigns can be paused, resumed and ended; ending sets the end date to
  today if it was later and can't be undone.
- **Funnel** for a date range (default: the last 90 days; a campaign's own page defaults to its
  start date): visitors = daily counts in the range; every later step follows the leads created in
  the range as they are today (leads → signed up → profile complete → qualified → applied →
  selected → deployed), using `LeadProgress` so it matches the CRM. Steps can exceed the one
  before (someone can apply without being "qualified"), so each step shows its share of the
  previous one rather than pretending to be a strict funnel. Computed with one grouped query per
  step. The index also shows totals and how many signed up without a campaign.
- **Campaign page:** link with copy button, QR code (black on white, downloadable SVG for banners)
  and visitors per day. The QR rendering moved to `App\Support\QrCode`, shared with cohort
  invites.
- **Admin overview:** CRM staff get a "Lead follow-ups due" tile (next follow-up today or
  earlier) linking to the CRM's Due today view.

### D-100 · Recruitment projects
- **`/org/projects`** (employers; anyone with `jobs.view` reads, `projects.manage` changes): a
  project has a name, target headcount, destination, source countries, optional target date, its
  jobs and a team (organization members). References run per organization (`RP-0001`). A job is
  in at most one project; choosing a job that is already in another project is refused with its
  name rather than moving it silently. Editing the job list releases jobs that were unticked.
- **Stage counts** (deck slide 18): Applied → Qualified → Interview → Selected → Visa and travel
  → Joined. A stage counts every applicant who ever reached it or a later stage, from the current
  status or the stage history, so skipping a stage or a later rejection doesn't remove anyone
  from the earlier counts. Visa and travel = an open or completed mobility case; Joined = a case
  closed as joined. The page shows joined against the target, each job's positions, applicants
  and joined, and the team.
- **Bulk actions across the project's jobs:** the project page lists every applicant of its jobs
  (filter by status) with the same select-and-move bar as a job pipeline (existing
  `org.applicants.move`), plus **bulk reject** (`org.applicants.reject-many`, new) with one reason
  the workers see; applicants who can't be rejected any more are skipped and counted. Both need
  `applications.manage` on every selected application.
- Completing or cancelling a project doesn't touch its jobs or applications; a completed project
  can be reopened, a cancelled one can't. Suspended organizations can't change projects (existing
  workspace lock).
- Agencies don't have projects yet (the deck puts Projects in the employer workspace).

### D-101 · Hiring analytics
- **`/org/analytics`** for employers and agencies (anyone with `applications.view`; institutes
  have their own placements page). Date range, default the last 90 days; everything is about
  applications made in that range. Counts and timings only, no scores or ranking (rule 12).
- Shows live jobs, applications, **median days from applying to first shortlist** and **to first
  offer** (from the stage history, Postgres `percentile_cont`), the stage counts (same "ever
  reached" rule as projects, now in `HiringStages`, shared by projects and analytics), applicants
  by the country they live in now (top eight), how they arrived (applied themselves / invited by
  the organization from talent search or a pool / withdrew), each job's stage counts (jobs with
  applications in the range or live, up to 50) and active projects against their targets.
- Spec §9's "institute pool" as a source isn't tracked separately: invitations from a pool and
  from talent search are both "invited by you".

### D-102 · Platform billing
- **Finance (`/admin/billing`, `finance.manage`):** three tabs.
  - *Organizations:* each organization's plan, credit balance and unpaid total. An organization's
    page shows plan, credits, seats and the last 20 credit entries, with forms to **change plan**
    (an active plan for that organization type, from a start date; the current plan ends the day
    before; optionally creates a draft invoice for the plan) and **adjust credits** (± with a
    reason, logged), and a "New invoice" button.
  - *Invoices:* waiting for payment / overdue / drafts / paid / void. A draft is edited line by
    line (description, quantity, unit price, optional posting credits per unit), then **issued**
    (number `INV-2026-00001`, due in `payment_days` = 14, owners and agency owners told in-app and
    by email, `invoice_issued`). An issued invoice is **marked paid** (method, bank reference,
    date) which adds its credits to the ledger once (plan lines as plan credits, other lines as
    purchases), or **voided** with a note. Paid invoices never change; voided ones keep their
    number. VAT 5% by default, rounded half up.
  - *Plans:* name, for which organization type, price (typed as a decimal, stored in minor units
    without floats), period, posting credits, seats (blank = unlimited), talent search, projects,
    offered or switched off. Plans are never deleted.
- **Organizations (`/org/billing`, `billing.manage`, employers and agencies):** plan and what it
  includes, posting credits (a negative balance is shown as "more used than bought"), seats used,
  credit history and issued invoices (never drafts). The invoice page prints cleanly (workspace
  chrome hidden in print) and shows how to pay: `BILLING_PAYMENT_INSTRUCTIONS` or "bank transfer
  quoting the invoice number". Every billing screen says platform billing is separate from worker
  job costs (rule 13).
- **Payment provider:** `PaymentProvider` contract bound from `blueskills.billing.provider`; only
  `manual` exists (no online payment link). A Stripe/other provider plugs in there later.
- **Limits (`BLUESKILLS_BILLING_ENFORCE`, off):** when on, a job's *first* submission needs a
  posting credit and its first publication uses one (resubmitting a live job is free; if credits
  ran out while it waited for review the balance can go below zero rather than blocking staff);
  inviting a member needs a free seat (members plus pending invitations); talent search needs a
  plan that includes it. While off, pages say limits are counted but not enforced.
- **Admin overview:** finance staff get "Overdue invoices".

### D-103 · Module 9 sample data, review fixes and deploy notes
- **Sample data** (`CrmAndBillingSampleSeeder`, after `SampleWorldSeeder`): CRM agent
  `m.akter@blueskills.test` and finance officer `r.nair@blueskills.test`; campaigns Dhaka job fair
  2026 (`/c/DHAKA-JOB-FAIR-2026`), Facebook Bangladesh electricians (job search), Meghna graduates
  2026 (partner institute) and a paused Nepal FM radio spot, each with 30 days of visits; a lead
  for every sample worker (stage worked out from their real progress, mixed follow-ups, some due
  today or overdue) and three job-fair prospects (one do-not-contact); project RP-0042 "200
  Electricians — Dubai" on ABC Contracting with AE-MT-0115 and AE-ST-0042; sample plans marked
  "not approved for launch"; ABC on Employer Standard (September invoice paid with 15 credits, one
  used, October invoice waiting), Northstar on Agency Professional (overdue invoice, goodwill
  credits, a draft).
- **Review fixes:** the lead page ticked every stage up to the furthest one (an applicant showed
  as "qualified"); each stage is now checked on its own (`LeadProgress::reached`). Invoice lines fit
  a 390px screen (quantity × price folds under the description; totals moved out of the table).
  Funnel shares under 1% read "<1%". Side panels no longer shrink in flex columns.
- **Deploy notes for Module 9:** run migrations; re-run `RolesAndPermissionsSeeder`
  (`projects.manage`); keep `BLUESKILLS_BILLING_ENFORCE=false` until pricing is approved; set
  `BILLING_PAYMENT_INSTRUCTIONS` (bank details) before issuing real invoices; `BILLING_PROVIDER`
  stays `manual`. No new scheduled tasks.

### D-104 · Module 10 scope (owner answers, 2026-10-22)
- **Parts:** 10a security headers, rate limits and a route audit test; 10d performance (trigram
  indexes, N+1 fixes, config caching, load-test script); 10e PWA manifest, offline page and error
  pages; 10f pilot mode, production seeder, `blueskills:create-superuser`, launch checklist;
  10g accessibility (axe) and RTL checks; 10h review and hand-over. Review once at the end.
- **Dropped by the owner:** worker data export, a `privacy:prune` retention task and the
  breach-response runbook (planned 10b); the `/health` endpoint, Horizon gate changes, failed-job
  alerts, error monitoring and backups (planned 10c). Sentry and `spatie/laravel-backup` were
  approved but belong to 10c, so they are not installed now; the launch checklist lists them.
- **Hosting:** no account yet. Production config stays host-neutral; deploy automation waits for a
  host (Laravel Cloud if it has a Middle East region, otherwise a UAE VPS).
- **Packages:** `@axe-core/playwright` (dev) approved.
- **Search:** stays on Postgres with `pg_trgm` indexes for the pilot; Scout + Meilisearch later.
- **Pilot:** workers register freely; organizations join with a staff-issued invite code; one
  live corridor BD → AE; billing enforcement stays off.
- **Retention defaults (agreed, not built in this module, need legal review):** OTP codes 7 days,
  campaign visit IPs removed after 90 days, read notifications 180 days, files of rejected or
  withdrawn documents 90 days, closed mobility case files 1 year after closing, audit log 2 years.

### D-105 · Security headers, rate limits and the route audit (10a)
- **`SecurityHeaders`** (global middleware) on every response, error pages included: a Content
  Security Policy with a per-request script nonce (Vite tags and the inline appearance script carry
  it; inline styles stay allowed because React and Radix set style attributes), `frame-ancestors
  'none'`, `object-src 'none'`, `form-action 'self'`; `X-Frame-Options: DENY`, `nosniff`,
  `Referrer-Policy: strict-origin-when-cross-origin`, `Cross-Origin-Opener-Policy: same-origin`,
  and a Permissions-Policy that lets only this site use camera and microphone (SkillShow,
  Voice-to-CV). HSTS (1 year, subdomains) only over HTTPS.
- The policy is **report-only outside production** (`BLUESKILLS_CSP_ENFORCE`), so a missing source
  shows in the browser console before it can break a page. `connect-src` allows the Reverb socket
  the browser uses (`VITE_REVERB_*`, which in Sail differs from the server's `REVERB_HOST`) and the
  Vite dev server while it runs. A private disk on another host (S3) must be added with
  `BLUESKILLS_CSP_MEDIA_SOURCES`; `BLUESKILLS_CSP_REPORT_URI` collects reports. Checked in
  Chromium on worker, employer and staff pages: no violations.
- **Sessions:** secure-only and encrypted cookies by default when `APP_ENV=production`.
- **Rate limits:** a `writes` limiter on the whole web group (reads never count; broadcasting
  auth excluded): 120 writes a minute per signed-in user, 300 per IP for guests, on top of each
  route's own limit. Worker phone sign-in (`worker-join`) was 10 a minute **per IP**, which would
  lock out a job fair on one Wi-Fi network after ten workers; it is now 10 a minute per session
  and 300 per IP. The per-IP OTP cap per hour is now `BLUESKILLS_OTP_MAX_PER_IP_PER_HOUR`
  (default 100, was 20); per-phone limits are unchanged (5 an hour, 60 s between codes).
- **Route audit test** (`tests/Feature/Security/RouteAuditTest.php`): every app route needs
  sign-in unless it is in the public list; every signed-in controller action must call a policy
  or gate (in the action, a controller method it calls, `can:` middleware or a FormRequest whose
  `authorize()` isn't `return true`) unless it is in the short "own records only" list; admin
  routes need staff + 2FA; Inertia devtools never record outside `local`. A new route that fails
  must authorize, or be added to a list with its reason.
- **CI** runs `composer audit` and `npm audit --omit=dev --audit-level=high`.
- Signed media links stay at 15 minutes (`MediaUrls::TTL_MINUTES`) and storage redirects at 5.

### D-106 · Performance at pilot volume (10d)
- **`VolumeSeeder`** (local only, refuses production): 50,000 discoverable workers with skills
  and a discovery consent, 3,000 jobs from the sample employers and 100,000 applications, by
  set-based SQL in about 20 s; references start with `VOL`. Measured with it:
  - Talent search with text took **3.9 s**: the discoverable scope and the text match used
    correlated `EXISTS` (`whereHas`) checks that Postgres ran once per profile. They are now
    uncorrelated `IN (subquery)` checks (hashed once). With the new indexes it takes **~110 ms**.
  - Every workspace page reloaded the member's permissions for each of the ~12 navigation
    abilities (10–14 repeated queries). `OrganizationPermissions` now remembers a member's
    permissions for the rest of the HTTP request (never in queue workers or commands, which
    run for many jobs); `QueryBudgetTest` guards it.
  - `/org/jobs` listed every job (≈1 s for 500); it is paginated at 25 like candidates.
  - Public job search stays under 100 ms with 3,000 jobs; admin and worker pages 100–200 ms.
- **Indexes** (migration `add_search_indexes`, needs the `pg_trgm` extension, standard on
  managed Postgres): trigram GIN on users.name, worker_profiles.headline, jobs.title,
  organizations.display_name and leads.name; partial indexes for discovery consents, the
  discoverable-worker ordering and published jobs by date.
- **N+1 guard:** `Model::preventLazyLoading()` everywhere; outside production a lazy load across
  a collection throws, in production it only logs a warning. It found four: bulk move/reject
  (job, its organization, the worker), the applicant page's shared documents and permissions
  loaded for roster members; all now eager loaded.
- **Not done, on purpose:** caching taxonomy and country lists. The public jobs page runs ten
  queries and the filter lists cost two cheap ones; caching would add invalidation for no
  measurable gain.
- **`scripts/load-test.mjs`** (Node only): virtual users browse job search and Truth Cards and,
  with `--email`, workspace pages; prints p50/p95/max per page. For staging; local numbers
  reflect Sail's single development server. The worker apply flow isn't covered (each apply
  needs its own phone sign-in).

### D-107 · Error pages, offline page and installable app (10e)
- **Error pages:** 403, 404 and 429 render `public/error` (Inertia, public layout, en/bn/RTL):
  what went wrong, what to do, "Go to my dashboard" (signed in) or "Go to the home page", and
  "Go back". Exception messages are never shown. Unknown URLs hit a fallback route, so the page
  knows who is signed in; errors raised before the Inertia middleware (a missing record in
  route binding, a rate limit) get the locale and shared props in `App\Support\ErrorPage`, with
  a translations-only fallback if the database is the problem.
- **419** (expired form) goes back to the page with a toast instead of an error page.
- **500 and 503** for full page loads are standalone Blade pages (`resources/views/errors/`)
  that need no database, session or JavaScript; an Inertia visit that fails with 500 gets the
  Inertia page. Local debug keeps Laravel's trace page. JSON requests keep JSON errors.
- **Brand icons:** the favicon and touch icon were still the starter kit's Laravel logo. They are
  now the BLUESKILLS mark (the `IdCard` tile from `components/bs/logo.tsx`), plus 192/512 and
  maskable 512 PNGs for the manifest.
- **Installable app:** `manifest.webmanifest` (standalone, brand colours, start `/?source=app`).
  `sw.js` is registered from production builds only and does one thing: when a full page load
  fails, it shows `offline.html` (en + bn, no JavaScript). It never caches pages or API
  responses, because they hold personal data. Bump `VERSION` in `sw.js` when the offline page
  changes.
- New `errors.*` keys and the offline page's Bangla are drafts in `docs/translation-todo.md`.

### D-108 · Pilot mode, safe seeding, first superuser and the launch checklist (10f)
- **Pilot mode** (`BLUESKILLS_PILOT`, off by default). While on, every page shows a pilot notice
  and an organization registers only with a **pilot invite code**. Codes are `PILOT-XXXXXX`
  (no 0/O/1/I/L), issued at Admin › Organizations › Pilot invite codes by staff who review
  organizations (`verifications.review`), for a named partner, optionally one organization
  type, a number of uses (1–50) and an end date. Codes are handed over by staff; nothing is
  sent. Redeeming locks the code row; unknown, used-up, expired and revoked codes get one
  message, a code for another type gets its own. Revoking keeps earlier registrations. The
  organization keeps `pilot_invite_id`; staff verification is unchanged. Account sign-up and
  worker registration need no code.
- **Corridors:** no new mechanism. Jobs already need a destination staff enabled; in pilot mode
  `CountrySeeder` opens only `blueskills.pilot.destinations` (AE) on a new database.
- **Seeding is safe on every deploy:** the reference seeders (countries, corridor rules,
  languages, taxonomy) used `updateOrCreate`, so re-seeding would have undone staff edits
  (corridor switches, rules, Bangla taxonomy names). They now only add what is missing. A
  separate production seeder isn't needed: `DatabaseSeeder` already skips sample data in
  production.
- **`blueskills:create-superuser`** (email argument, `--name`, password prompted, production
  password rules) creates the first staff superuser without putting a password in `.env`.
- **Trusted proxies** (`TRUSTED_PROXIES` → `config/trustedproxy.php`): behind Cloudflare the app
  otherwise sees one proxy IP for everyone (breaking per-IP limits) and misses HTTPS.
- **`docs/launch-checklist.md`** collects approvals, provider accounts, server, environment,
  first and later deploys, pilot set-up, staging checks and what is still not built.
