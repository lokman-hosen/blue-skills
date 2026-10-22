<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Indexes for search at pilot volume (D-106). Trigram (pg_trgm) GIN indexes serve the
 * `ilike '%term%'` searches; partial indexes cover the discoverable-worker filter and the
 * default orderings of talent and job search.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create extension if not exists pg_trgm');

        DB::statement('create index users_name_trgm_index on users using gin (name gin_trgm_ops)');
        DB::statement('create index worker_profiles_headline_trgm_index on worker_profiles using gin (headline gin_trgm_ops)');
        DB::statement('create index jobs_title_trgm_index on jobs using gin (title gin_trgm_ops)');
        DB::statement('create index organizations_display_name_trgm_index on organizations using gin (display_name gin_trgm_ops)');
        DB::statement('create index leads_name_trgm_index on leads using gin (name gin_trgm_ops)');

        DB::statement("create index consents_discovery_index on consents (worker_profile_id) where purpose = 'discovery' and revoked_at is null");
        DB::statement("create index worker_profiles_discoverable_index on worker_profiles (updated_at desc, id) where visibility = 'employers_and_agencies' and suspended_at is null and occupation_id is not null");
        DB::statement('create index jobs_status_published_at_index on jobs (status, published_at desc) where deleted_at is null');
    }

    public function down(): void
    {
        foreach ([
            'users_name_trgm_index', 'worker_profiles_headline_trgm_index', 'jobs_title_trgm_index',
            'organizations_display_name_trgm_index', 'leads_name_trgm_index', 'consents_discovery_index',
            'worker_profiles_discoverable_index', 'jobs_status_published_at_index',
        ] as $index) {
            DB::statement("drop index if exists {$index}");
        }
    }
};
