<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 6: licence scope, mandate scope and employer confirmation (product rule 10), and
     * mobility cases with history and per-item documents (spec §6, product rules 6 and 8).
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Destination countries an agency licence covers, e.g. ["AE","SA"].
            $table->jsonb('licence_countries')->nullable();
        });

        Schema::table('job_mandates', function (Blueprint $table) {
            // Room for `awaiting_employer` (D-074).
            $table->string('status', 32)->change();
            $table->jsonb('occupation_ids')->nullable();
            $table->char('destination_country_code', 2)->nullable();
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('employer_responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('employer_responded_at')->nullable();
            $table->string('employer_note', 500)->nullable();
        });

        Schema::table('mobility_cases', function (Blueprint $table) {
            $table->string('reference', 32)->nullable()->unique();
            $table->foreignUlid('managing_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignUlid('employer_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignUlid('agency_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignUlid('handler_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('closed_reason', 32)->nullable();
            $table->timestampTz('closed_at')->nullable();

            $table->index('managing_organization_id');
            $table->index('employer_organization_id');
        });

        Schema::table('mobility_case_stages', function (Blueprint $table) {
            // What the worker should do now, shown on their case page.
            $table->string('next_action', 500)->nullable();
            $table->string('blocked_reason', 500)->nullable();
        });

        Schema::create('mobility_case_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('mobility_case_id')->constrained()->cascadeOnDelete();
            $table->string('stage_key', 64)->nullable();
            $table->string('action', 32);
            $table->foreignUlid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestampsTz();

            $table->index(['mobility_case_id', 'created_at']);
        });

        Schema::create('case_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('mobility_case_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('title', 120);
            // requested | uploaded | accepted | returned
            $table->string('status', 16);
            // worker | organization: who provides the file
            $table->string('provided_by', 16);
            $table->foreignUlid('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->boolean('employer_can_view')->default(true);
            $table->string('reason_code', 64)->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('shared_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->date('due_on')->nullable();
            $table->timestampsTz();

            $table->index(['mobility_case_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_documents');
        Schema::dropIfExists('mobility_case_events');

        Schema::table('mobility_case_stages', function (Blueprint $table) {
            $table->dropColumn(['next_action', 'blocked_reason']);
        });

        Schema::table('mobility_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('managing_organization_id');
            $table->dropConstrainedForeignId('employer_organization_id');
            $table->dropConstrainedForeignId('agency_organization_id');
            $table->dropConstrainedForeignId('handler_user_id');
            $table->dropColumn(['reference', 'closed_reason', 'closed_at']);
        });

        Schema::table('job_mandates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropConstrainedForeignId('employer_responded_by');
            $table->dropColumn(['occupation_ids', 'destination_country_code', 'employer_responded_at', 'employer_note']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('licence_countries');
        });
    }
};
