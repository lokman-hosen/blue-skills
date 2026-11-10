<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fills the new fields of existing jobs from their older numeric ones (D-117). Only clear
     * mappings are made: a value that fits no option becomes "other" with the old value as text,
     * and nothing is guessed (worker fees stay unknown unless the job already lists a worker cost).
     * Plain SQL so later enum changes cannot alter what this migration did.
     */
    public function up(): void
    {
        $hours = "trim(trailing '.' from trim(trailing '0' from hours_per_day::text))";

        DB::statement(<<<SQL
            update jobs set
                working_hours = case
                    when hours_per_day >= 6 and hours_per_day <= 8 then 'day_6_8'
                    when hours_per_day > 8 and hours_per_day <= 10 then 'day_8_10'
                    when hours_per_day > 10 and hours_per_day <= 12 then 'day_10_12'
                    else 'other' end,
                working_hours_other = case
                    when hours_per_day >= 6 and hours_per_day <= 12 then null
                    else {$hours} || ' hours/day' end
            where hours_per_day is not null and working_hours is null
            SQL);

        DB::statement(<<<'SQL'
            update jobs set
                working_days = case days_per_week when 5 then 'days_5' when 6 then 'days_6' else 'other' end,
                working_days_other = case when days_per_week in (5, 6) then null else days_per_week || ' days/week' end
            where days_per_week is not null and working_days is null
            SQL);

        DB::statement(<<<'SQL'
            update jobs set
                contract_duration = case contract_months
                    when 3 then '3_months' when 6 then '6_months' when 9 then '9_months'
                    when 12 then '12_months' when 24 then '2_years' else 'other' end,
                contract_other = case when contract_months in (3, 6, 9, 12, 24) then null else contract_months || ' months' end
            where contract_months is not null and contract_duration is null
            SQL);

        DB::statement(<<<'SQL'
            update jobs set experience_level = case
                    when min_experience_years <= 0 then 'none'
                    when min_experience_years < 3 then '1_3'
                    when min_experience_years < 5 then '3_5'
                    when min_experience_years < 10 then '5_10'
                    else '10_plus' end
            where min_experience_years is not null and experience_level is null
            SQL);

        DB::statement(<<<'SQL'
            update jobs set worker_fees = 'apply'
            where worker_fees is null and (
                visa_payer = 'worker' or permit_payer = 'worker'
                or exists (select 1 from job_costs where job_costs.job_id = jobs.id and job_costs.payer = 'worker')
            )
            SQL);
    }

    /**
     * The numeric columns were never changed, so rolling back only clears what was filled.
     */
    public function down(): void
    {
        DB::table('jobs')->update([
            'working_hours' => null, 'working_hours_other' => null,
            'working_days' => null, 'working_days_other' => null,
            'contract_duration' => null, 'contract_other' => null,
            'experience_level' => null, 'worker_fees' => null,
        ]);
    }
};
