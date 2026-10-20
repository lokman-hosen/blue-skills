<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->foreignUlid('occupation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('specialization_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->char('current_country_code', 2)->nullable();
            $table->string('passport_status', 16)->nullable();
            $table->string('availability', 32)->nullable();
            $table->string('visibility', 32);
            $table->unsignedBigInteger('salary_expectation_minor')->nullable();
            $table->char('salary_currency', 3)->nullable();
            $table->timestampTz('profile_completed_at')->nullable();
            $table->timestampsTz();

            $table->foreign('current_country_code')->references('code')->on('countries');
            $table->index(['occupation_id', 'current_country_code']);
            $table->index('visibility');
        });

        Schema::create('worker_experiences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->char('country_code', 2);
            $table->string('employer_name')->nullable();
            $table->string('role');
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->text('description')->nullable();
            $table->timestampsTz();

            $table->foreign('country_code')->references('code')->on('countries');
            $table->index('worker_profile_id');
        });

        Schema::create('worker_skills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('skill_id')->constrained()->cascadeOnDelete();
            $table->string('source', 16);
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['worker_profile_id', 'skill_id']);
            $table->index('skill_id');
        });

        Schema::create('worker_languages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->string('proficiency', 16);
            $table->timestampsTz();

            $table->foreign('language_code')->references('code')->on('languages');
            $table->unique(['worker_profile_id', 'language_code']);
        });

        Schema::create('worker_destination_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->char('country_code', 2);
            $table->unsignedSmallInteger('priority')->default(1);
            $table->timestampsTz();

            $table->foreign('country_code')->references('code')->on('countries');
            $table->unique(['worker_profile_id', 'country_code']);
            $table->index('country_code');
        });

        Schema::create('voice_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->string('question_key', 64);
            $table->string('locale', 10);
            $table->text('transcript')->nullable();
            $table->jsonb('extracted')->nullable();
            $table->string('status', 16);
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampsTz();

            $table->index(['worker_profile_id', 'question_key']);
            $table->index('status');
        });

        Schema::create('skill_shows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('visibility', 32);
            $table->string('moderation_status', 16);
            $table->foreignUlid('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('moderated_at')->nullable();
            $table->timestampsTz();

            $table->index('worker_profile_id');
            $table->index('moderation_status');
        });

        Schema::create('skill_show_skill', function (Blueprint $table) {
            $table->foreignUlid('skill_show_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('skill_id')->constrained()->cascadeOnDelete();

            $table->primary(['skill_show_id', 'skill_id']);
            $table->index('skill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_show_skill');
        Schema::dropIfExists('skill_shows');
        Schema::dropIfExists('voice_answers');
        Schema::dropIfExists('worker_destination_preferences');
        Schema::dropIfExists('worker_languages');
        Schema::dropIfExists('worker_skills');
        Schema::dropIfExists('worker_experiences');
        Schema::dropIfExists('worker_profiles');
    }
};
