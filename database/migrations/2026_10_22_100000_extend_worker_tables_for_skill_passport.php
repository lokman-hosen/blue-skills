<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 2: Skill Passport reference, passport expiry year, salary period, years-only
     * experience, typed voice answers and SkillShow reason codes (D-047).
     */
    public function up(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            // Public Passport number (BSK-0004182). Random, so it reveals nothing about sign-up order.
            $table->string('reference', 16)->nullable()->unique();
            // Workers say "valid until 2030"; we store only what they said, never a guessed day.
            $table->unsignedSmallInteger('passport_valid_until_year')->nullable();
            $table->string('salary_period', 16)->nullable();
        });

        DB::table('worker_profiles')->whereNull('reference')->orderBy('id')->each(function (object $profile): void {
            do {
                $reference = 'BSK-'.str_pad((string) random_int(0, 9_999_999), 7, '0', STR_PAD_LEFT);
            } while (DB::table('worker_profiles')->where('reference', $reference)->exists());

            DB::table('worker_profiles')->where('id', $profile->id)->update(['reference' => $reference]);
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->string('reference', 16)->nullable(false)->change();
        });

        Schema::table('worker_experiences', function (Blueprint $table) {
            // "Five years in Bangladesh" is a valid answer without exact dates.
            $table->unsignedSmallInteger('years')->nullable();
            $table->date('started_on')->nullable()->change();
        });

        Schema::table('voice_answers', function (Blueprint $table) {
            $table->boolean('typed')->default(false);
            $table->string('error_code', 32)->nullable();
        });

        Schema::table('skill_shows', function (Blueprint $table) {
            $table->string('reason_code', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('skill_shows', function (Blueprint $table) {
            $table->dropColumn('reason_code');
        });

        Schema::table('voice_answers', function (Blueprint $table) {
            $table->dropColumn(['typed', 'error_code']);
        });

        Schema::table('worker_experiences', function (Blueprint $table) {
            $table->dropColumn('years');
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn(['reference', 'passport_valid_until_year', 'salary_period']);
        });
    }
};
