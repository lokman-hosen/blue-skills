<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 4: who handles an application, its stage history, dated and revocable per-item
     * consent, interviews, offers and shortlists (spec §6, §8, product rules 6–7).
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignUlid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('skill_show_id')->nullable()->constrained('skill_shows')->nullOnDelete();
            $table->timestampTz('stage_changed_at')->nullable();
            // The worker acknowledged material job changes up to this time (product rule 5).
            $table->timestampTz('job_change_seen_at')->nullable();
        });

        Schema::table('application_consents', function (Blueprint $table) {
            // The exact file shared for document items, so a replaced scan is never shared silently.
            $table->foreignUlid('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestampTz('granted_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
        });

        Schema::create('application_stage_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignUlid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason_code', 64)->nullable();
            $table->text('note')->nullable();
            $table->timestampsTz();

            $table->index(['application_id', 'created_at']);
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('mode', 16);
            $table->string('location')->nullable();
            $table->text('instructions')->nullable();
            $table->string('outcome', 16)->nullable();
            $table->text('outcome_note')->nullable();
            $table->foreignUlid('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['application_id', 'scheduled_at']);
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('salary_amount_minor');
            $table->char('salary_currency', 3);
            $table->string('salary_period', 16);
            $table->date('start_on')->nullable();
            $table->date('respond_by')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 16);
            $table->timestampTz('responded_at')->nullable();
            $table->foreignUlid('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['application_id', 'status']);
        });

        Schema::create('shortlists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->foreignUlid('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index('organization_id');
        });

        Schema::create('shortlist_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('shortlist_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->foreignUlid('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['shortlist_id', 'worker_profile_id']);
            $table->index('worker_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlist_entries');
        Schema::dropIfExists('shortlists');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('application_stage_events');

        Schema::table('application_consents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_id');
            $table->dropColumn(['granted_at', 'revoked_at']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropConstrainedForeignId('skill_show_id');
            $table->dropColumn(['stage_changed_at', 'job_change_seen_at']);
        });
    }
};
