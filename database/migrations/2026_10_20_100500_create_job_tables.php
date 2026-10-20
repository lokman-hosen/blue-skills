<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The structured job object behind the Job Truth Card (spec §5). Nullable
     * condition columns mean "Not disclosed"; never default them to a guess.
     */
    public function up(): void
    {
        Schema::create('job_mandates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employer_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUlid('agency_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('reference', 64);
            $table->unsignedInteger('headcount');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 16);
            $table->timestampsTz();

            $table->unique(['agency_organization_id', 'reference']);
            $table->index('employer_organization_id');
            $table->index('status');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('employer_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUlid('agency_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignUlid('job_mandate_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('public_code', 16)->unique();
            $table->string('title');
            $table->foreignUlid('occupation_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('min_experience_years')->nullable();
            $table->jsonb('language_requirements')->nullable();
            $table->char('destination_country_code', 2);
            $table->string('destination_city')->nullable();
            $table->unsignedInteger('headcount');

            $table->unsignedBigInteger('salary_amount_minor')->nullable();
            $table->char('salary_currency', 3)->nullable();
            $table->string('salary_period', 8)->nullable();
            $table->decimal('hours_per_day', 4, 2)->nullable();
            $table->unsignedSmallInteger('days_per_week')->nullable();
            $table->text('overtime_terms')->nullable();
            $table->unsignedSmallInteger('contract_months')->nullable();

            $table->string('accommodation', 16)->nullable();
            $table->string('accommodation_details')->nullable();
            $table->string('food', 16)->nullable();
            $table->string('food_details')->nullable();
            $table->string('transport', 16)->nullable();
            $table->string('transport_details')->nullable();
            $table->string('medical', 16)->nullable();
            $table->string('medical_details')->nullable();
            $table->string('airfare', 16)->nullable();
            $table->string('airfare_details')->nullable();
            $table->text('other_benefits')->nullable();

            $table->string('permit_payer', 16)->nullable();
            $table->string('visa_payer', 16)->nullable();
            $table->date('joining_from')->nullable();
            $table->date('joining_to')->nullable();
            $table->date('deadline')->nullable();

            $table->string('status', 32);
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('destination_country_code')->references('code')->on('countries');
            $table->index('status');
            $table->index(['occupation_id', 'destination_country_code']);
            $table->index('employer_organization_id');
            $table->index('agency_organization_id');
            $table->index('organization_id');
        });

        Schema::create('job_costs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('payer', 16);
            $table->timestampsTz();

            $table->index('job_id');
        });

        Schema::create('job_skill_requirements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('skill_id')->constrained()->restrictOnDelete();
            $table->boolean('required')->default(true);
            $table->timestampsTz();

            $table->unique(['job_id', 'skill_id']);
            $table->index('skill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_skill_requirements');
        Schema::dropIfExists('job_costs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_mandates');
    }
};
