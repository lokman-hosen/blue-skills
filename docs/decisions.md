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
