<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Universal job posting (D-117): the fields of the job posting prototype. Every column is
     * nullable or defaulted, so existing jobs stay valid; a null still reads "Not disclosed".
     * `hours_per_day` and `days_per_week` stay until a later release drops them.
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('employment_type', 16)->nullable();
            $table->unsignedBigInteger('salary_max_amount_minor')->nullable();
            $table->string('salary_basis', 8)->nullable();
            $table->string('working_hours', 16)->nullable();
            $table->string('working_hours_other', 120)->nullable();
            $table->string('working_days', 16)->nullable();
            $table->string('working_days_other', 120)->nullable();
            $table->string('shift_pattern', 16)->nullable();
            $table->string('shift_pattern_other', 120)->nullable();
            $table->unsignedBigInteger('accommodation_cost_amount_minor')->nullable();
            $table->string('insurance', 16)->nullable();
            $table->string('insurance_details')->nullable();
            $table->string('experience_level', 16)->nullable();
            $table->string('experience_other', 120)->nullable();
            $table->string('education_level', 16)->nullable();
            $table->string('education_other', 120)->nullable();
            $table->string('age_range', 8)->nullable();
            $table->string('age_other', 120)->nullable();
            $table->string('other_skills', 500)->nullable();
            $table->text('duties')->nullable();
            $table->string('contract_duration', 16)->nullable();
            $table->string('contract_other', 120)->nullable();
            $table->string('worker_fees', 8)->nullable();
            $table->string('apply_method', 16)->default('blueskills');
            $table->string('apply_url', 2048)->nullable();
            $table->text('additional_details')->nullable();

            $table->index('employment_type');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['employment_type']);
            $table->dropColumn([
                'employment_type', 'salary_max_amount_minor', 'salary_basis',
                'working_hours', 'working_hours_other', 'working_days', 'working_days_other',
                'shift_pattern', 'shift_pattern_other', 'accommodation_cost_amount_minor',
                'insurance', 'insurance_details', 'experience_level', 'experience_other',
                'education_level', 'education_other', 'age_range', 'age_other', 'other_skills', 'duties',
                'contract_duration', 'contract_other', 'worker_fees', 'apply_method', 'apply_url',
                'additional_details',
            ]);
        });
    }
};
