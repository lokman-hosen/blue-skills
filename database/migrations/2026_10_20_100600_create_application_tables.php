<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An application and a mobility case are separate entities (spec §6).
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('worker_profile_id')->constrained()->restrictOnDelete();
            $table->string('status', 32);
            $table->timestampTz('applied_at');
            $table->timestampsTz();

            $table->unique(['job_id', 'worker_profile_id']);
            $table->index('worker_profile_id');
            $table->index('status');
        });

        Schema::create('profile_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('data');
            $table->timestampTz('taken_at');
            $table->timestampsTz();
        });

        Schema::create('application_consents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->string('item', 32);
            $table->boolean('granted');
            $table->timestampsTz();

            $table->unique(['application_id', 'item']);
        });

        Schema::create('mobility_cases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->unique()->constrained()->restrictOnDelete();
            $table->string('template', 64);
            $table->string('current_stage', 64)->nullable();
            $table->string('status', 16);
            $table->timestampsTz();

            $table->index('status');
        });

        Schema::create('mobility_case_stages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('mobility_case_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->unsignedSmallInteger('position');
            $table->string('status', 16);
            $table->date('due_on')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->foreignUlid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['mobility_case_id', 'key']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobility_case_stages');
        Schema::dropIfExists('mobility_cases');
        Schema::dropIfExists('application_consents');
        Schema::dropIfExists('profile_snapshots');
        Schema::dropIfExists('applications');
    }
};
