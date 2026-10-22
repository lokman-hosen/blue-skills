<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pilot-scale volume for checking query plans and load locally (D-106): discoverable workers
 * with skills and a discovery consent, published jobs from the sample employers and
 * applications between them, inserted with set-based SQL in seconds. Run after the normal seed:
 * `sail artisan db:seed --class=VolumeSeeder`. Every row's reference starts with VOL.
 * Never runs in production.
 */
class VolumeSeeder extends Seeder
{
    public const WORKERS = 50_000;

    public const JOBS = 3_000;

    public const APPLICATIONS = 100_000;

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('VolumeSeeder adds fictional bulk data and never runs in production.');
        }

        // A ULID-shaped key: 26 Crockford characters, first one 0 so it always parses.
        $ulid = "'0' || upper(substr(md5(random()::text || clock_timestamp()::text), 1, 25))";

        DB::transaction(function () use ($ulid): void {
            DB::statement("create temporary table vol_workers on commit drop as
                select n, {$ulid} as user_id, {$ulid} as profile_id from generate_series(1, ?) n", [self::WORKERS]);

            DB::statement("insert into users (id, type, name, phone, locale, timezone, data_region, phone_verified_at, created_at, updated_at)
                select user_id, 'worker', 'Volume Worker ' || n, '+8801900' || lpad(n::text, 6, '0'), 'bn', 'Asia/Dhaka', 'ae', now(), now(), now()
                from vol_workers");

            DB::statement("insert into worker_profiles (id, user_id, visibility, reference, occupation_id, current_country_code, headline, years_experience, created_at, updated_at)
                select w.profile_id, w.user_id, 'employers_and_agencies', 'VOL-' || lpad(w.n::text, 6, '0'),
                    (select id from occupations order by random() + w.n * 0 limit 1),
                    (array['BD', 'NP', 'IN', 'PK', 'LK'])[1 + w.n % 5],
                    'Skilled worker with ' || (w.n % 20) || ' years on site', w.n % 20,
                    now() - (w.n % 365) * interval '1 day', now() - (w.n % 365) * interval '1 day'
                from vol_workers w");

            DB::statement("insert into consents (id, worker_profile_id, purpose, granted_at, created_at, updated_at)
                select {$ulid}, profile_id, 'discovery', now(), now(), now() from vol_workers");

            DB::statement("insert into worker_skills (id, worker_profile_id, skill_id, source, created_at, updated_at)
                select {$ulid}, w.profile_id, s.id, 'self_declared', now(), now()
                from vol_workers w
                cross join lateral (select id from skills order by random() + w.n * 0 limit 3) s");

            DB::statement("insert into jobs (id, organization_id, employer_organization_id, public_code, title, occupation_id, destination_country_code, destination_city, headcount, status, published_at, salary_amount_minor, salary_currency, created_at, updated_at)
                select {$ulid}, o.id, o.id, 'VOL-' || lpad(n::text, 5, '0'),
                    (array['Electrician', 'HVAC Technician', 'Plumber', 'Steel Fixer', 'Welder', 'Mason'])[1 + n % 6] || ' — batch ' || n,
                    (select id from occupations order by random() + n * 0 limit 1),
                    (array['AE', 'SA', 'QA'])[1 + n % 3], (array['Dubai', 'Riyadh', 'Doha'])[1 + n % 3],
                    1 + n % 50, case when n % 10 = 0 then 'closed' else 'published' end,
                    now() - (n % 120) * interval '1 day', 150000 + (n % 20) * 10000, 'AED', now(), now()
                from generate_series(1, ?) n
                cross join lateral (select id from organizations where type = 'employer' order by random() + n * 0 limit 1) o", [self::JOBS]);

            DB::statement("insert into applications (id, job_id, worker_profile_id, status, applied_at, created_at, updated_at)
                select {$ulid}, j.id, w.profile_id, (array['applied', 'reviewed', 'shortlisted', 'interview', 'rejected'])[1 + pairs.n % 5],
                    now() - (pairs.n % 90) * interval '1 day', now(), now()
                from (select distinct on (job_n, worker_n) n, 1 + (n::bigint * 7919) % ? as job_n, 1 + (n::bigint * 104729) % ? as worker_n from generate_series(1, ?) n) pairs
                join (select id, row_number() over (order by public_code) as job_n from jobs where public_code like 'VOL-%') j using (job_n)
                join vol_workers w on w.n = pairs.worker_n", [self::JOBS, self::WORKERS, self::APPLICATIONS]);
        });

        DB::statement('analyze');
    }
}
