<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 8: trust cases with evidence, actions and appeals (spec §10), report triage, worker
     * suspension, and the per-corridor legal review marker (D-088).
     */
    public function up(): void
    {
        Schema::create('trust_cases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // TC-2026-0001
            $table->string('reference', 20)->unique();
            // What the case is about: an organization, a job or a worker profile.
            $table->ulidMorphs('subject');
            $table->string('category', 32);
            // low | normal | high | urgent
            $table->string('priority', 16);
            // open | investigating | action_taken | closed
            $table->string('status', 16);
            $table->string('summary', 500);
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outcome_code', 64)->nullable();
            $table->foreignUlid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'priority']);
            $table->index('assigned_to');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreignUlid('trust_case_id')->nullable()->constrained()->nullOnDelete();
            // What the reporter is told: action_taken | no_action (D-088).
            $table->string('outcome', 16)->nullable();
            $table->string('reason_code', 64)->nullable();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
        });

        // Case history notes; files attach to a note on the private disk. Never edited.
        Schema::create('trust_case_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('trust_case_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestampsTz();

            $table->index(['trust_case_id', 'created_at']);
        });

        // Suspensions and restorations, append-only.
        Schema::create('trust_actions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('trust_case_id')->nullable()->constrained()->nullOnDelete();
            $table->ulidMorphs('subject');
            // suspend | restore
            $table->string('action', 16);
            $table->string('reason_code', 64);
            $table->string('note', 1000)->nullable();
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('appeals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // The suspension appealed against.
            $table->foreignUlid('trust_action_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('appellant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('statement');
            // submitted | upheld | overturned
            $table->string('status', 16);
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_note', 1000)->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();

            $table->index('status');
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            // Hidden from talent search and pools and can't apply while set (D-088).
            $table->timestampTz('suspended_at')->nullable();
        });

        Schema::table('countries', function (Blueprint $table) {
            // A corridor without legal review shows a staff-only flag (D-088).
            $table->timestampTz('legal_reviewed_at')->nullable();
            $table->foreignUlid('legal_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('legal_reviewed_by');
            $table->dropColumn('legal_reviewed_at');
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropColumn('suspended_at');
        });

        Schema::dropIfExists('appeals');
        Schema::dropIfExists('trust_actions');
        Schema::dropIfExists('trust_case_notes');

        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trust_case_id');
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['outcome', 'reason_code', 'decided_at']);
        });

        Schema::dropIfExists('trust_cases');
    }
};
