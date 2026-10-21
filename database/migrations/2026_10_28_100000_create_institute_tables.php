<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 7: courses, cohorts and graduates who opted in, training attestations and trade
     * assessments issued as worker evidence, and institute talent pools (spec §7, product rule 11).
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->foreignUlid('occupation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('duration_weeks')->nullable();
            $table->text('description')->nullable();
            $table->timestampsTz();

            $table->index('organization_id');
        });

        Schema::create('cohorts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('course_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            // Public invite link /i/{invite_code}.
            $table->string('invite_code', 16)->unique();
            // open | closed: a closed cohort takes no new graduates.
            $table->string('status', 16);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('cohort_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('cohort_id')->constrained()->cascadeOnDelete();
            // Set only once the worker accepts (product rule 11).
            $table->foreignUlid('worker_profile_id')->nullable()->constrained()->cascadeOnDelete();
            // Optional roster line the institute typed, to track who joined (D-082).
            $table->string('roster_name', 120)->nullable();
            $table->string('roster_phone', 20)->nullable();
            // invited | accepted | declined | left
            $table->string('status', 16);
            // roster | link
            $table->string('source', 16);
            // The graduate lets the institute follow their applications and placement (D-082).
            $table->boolean('shares_progress')->default(false);
            $table->timestampTz('responded_at')->nullable();
            $table->timestampTz('left_at')->nullable();
            $table->timestampsTz();

            $table->unique(['cohort_id', 'worker_profile_id']);
            $table->index(['cohort_id', 'status']);
            $table->index('roster_phone');
        });

        Schema::create('training_attestations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cohort_member_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            // The course as it was when attested; later course edits don't change it.
            $table->string('course_title', 160);
            $table->date('completed_on');
            $table->string('note', 500)->nullable();
            // issued | withdrawn
            $table->string('status', 16);
            $table->foreignUlid('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->string('withdrawn_reason', 500)->nullable();
            $table->timestampsTz();

            $table->index(['worker_profile_id', 'status']);
        });

        Schema::create('trade_assessments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cohort_member_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('occupation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            // pass | fail
            $table->string('result', 8);
            $table->unsignedTinyInteger('score')->nullable();
            $table->date('assessed_on');
            $table->string('note', 500)->nullable();
            // issued | withdrawn
            $table->string('status', 16);
            $table->foreignUlid('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->string('withdrawn_reason', 500)->nullable();
            $table->timestampsTz();

            $table->index(['worker_profile_id', 'status']);
        });

        Schema::create('talent_pools', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->foreignUlid('occupation_id')->nullable()->constrained()->nullOnDelete();
            // Set while verified employers and agencies may browse the pool (D-082).
            $table->timestampTz('published_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['organization_id', 'published_at']);
        });

        Schema::create('talent_pool_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('talent_pool_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['talent_pool_id', 'worker_profile_id']);
            $table->index('worker_profile_id');
        });

        Schema::table('evidence_items', function (Blueprint $table) {
            // The worker hides an issuer's item from their Skill Passport without changing it (D-082).
            $table->timestampTz('hidden_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('evidence_items', function (Blueprint $table) {
            $table->dropColumn('hidden_at');
        });

        Schema::dropIfExists('talent_pool_members');
        Schema::dropIfExists('talent_pools');
        Schema::dropIfExists('trade_assessments');
        Schema::dropIfExists('training_attestations');
        Schema::dropIfExists('cohort_members');
        Schema::dropIfExists('cohorts');
        Schema::dropIfExists('courses');
    }
};
