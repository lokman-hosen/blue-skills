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
