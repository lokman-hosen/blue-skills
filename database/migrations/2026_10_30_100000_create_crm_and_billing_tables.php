<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 9: worker CRM and campaign analytics (spec §9), recruitment projects (spec §6) and
     * platform billing, a ledger kept apart from worker job costs (spec §10–11, D-095).
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Used in the campaign link /c/{code}; never changes.
            $table->string('code', 24)->unique();
            $table->string('name');
            // social | job_fair | partner | institute | radio_tv | sms_whatsapp | referral | other
            $table->string('channel', 24);
            $table->char('source_country_code', 2)->nullable();
            // The partner institute or agency that runs it, when there is one.
            $table->foreignUlid('partner_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            // join | jobs: where the link sends people.
            $table->string('landing', 16);
            // active | paused | ended
            $table->string('status', 16);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index('status');
        });

        // Daily counts only: no per-person tracking of visitors (D-095).
        Schema::create('campaign_visits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('visits')->default(0);

            $table->unique(['campaign_id', 'day']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Set once the person has a worker account; a hand-entered lead may never get one.
            $table->foreignUlid('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            // Hand-entered leads only; a registered worker's name and phone come from the account.
            $table->string('name')->nullable();
            $table->string('phone', 16)->nullable();
            // campaign | institute | direct | walk_in | call_centre | referral
            $table->string('source', 16);
            $table->foreignUlid('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('partner_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->char('country_code', 2)->nullable();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            // new | contacted | no_answer | call_back | not_interested | do_not_contact
            $table->string('follow_up_status', 16);
            $table->timestampTz('next_follow_up_at')->nullable();
            $table->timestampTz('last_contacted_at')->nullable();
            // Worked out from what the worker did, never set by hand (D-095):
            // prospect | registered | profile_complete | qualified | applied | selected | deployed
            $table->string('conversion_stage', 24);
            $table->timestampTz('stage_reached_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['follow_up_status', 'next_follow_up_at']);
            $table->index('assigned_to');
            $table->index(['campaign_id', 'conversion_stage']);
            $table->index('phone');
        });

        // Append-only log of calls, messages and visits.
        Schema::create('lead_contact_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('staff_id')->nullable()->constrained('users')->nullOnDelete();
            // phone_call | whatsapp | sms | email | in_person
            $table->string('channel', 16);
            // reached | no_answer | wrong_number | call_back | not_interested
            $table->string('outcome', 16);
            $table->string('note', 1000)->nullable();
            $table->timestampTz('attempted_at');
            $table->timestampsTz();

            $table->index(['lead_id', 'attempted_at']);
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            // "BLUESKILLS may contact me about my profile" turned off (D-095).
            $table->timestampTz('staff_contact_opted_out_at')->nullable();
        });

        Schema::create('recruitment_projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            // RP-0042, running per organization.
            $table->string('reference', 20);
            $table->string('name');
            $table->unsignedInteger('target_headcount');
            $table->char('destination_country_code', 2)->nullable();
            $table->json('source_country_codes');
            // active | completed | cancelled
            $table->string('status', 16);
            $table->date('target_date')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['organization_id', 'reference']);
        });

        Schema::create('recruitment_project_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('recruitment_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestampsTz();

            $table->unique(['recruitment_project_id', 'user_id']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            // A job belongs to at most one project.
            $table->foreignUlid('recruitment_project_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('description', 500)->nullable();
            // employer | agency | institute: agency and training fees are set separately (spec §11).
            $table->string('organization_type', 16);
            $table->bigInteger('price_amount_minor');
            $table->char('price_currency', 3);
            // monthly | yearly | one_off
            $table->string('billing_period', 16);
            // Posting credits granted each period (one-off: once).
            $table->unsignedInteger('posting_credits')->default(0);
            // Null means unlimited seats.
            $table->unsignedInteger('seats')->nullable();
            $table->boolean('talent_search')->default(false);
            $table->boolean('recruitment_projects')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('plan_id')->constrained()->restrictOnDelete();
            // active | cancelled | ended
            $table->string('status', 16);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // INV-2026-00001, given when issued.
            $table->string('number', 20)->nullable()->unique();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('organization_subscription_id')->nullable()->constrained()->nullOnDelete();
            // draft | issued | paid | void
            $table->string('status', 16);
            $table->char('currency', 3);
            $table->bigInteger('subtotal_minor')->default(0);
            // Basis points: 500 = 5% VAT.
            $table->unsignedSmallInteger('tax_rate_bp')->default(0);
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('total_minor')->default(0);
            $table->date('due_on')->nullable();
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            // bank_transfer | card | cash | other
            $table->string('payment_method', 16)->nullable();
            $table->string('payment_reference', 100)->nullable();
            // Which payment provider settled it; `manual` until one is added (D-095).
            $table->string('provider', 32)->default('manual');
            $table->string('provider_reference', 100)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('voided_at')->nullable();
            $table->timestampsTz();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_amount_minor');
            $table->bigInteger('amount_minor');
            $table->foreignUlid('plan_id')->nullable()->constrained()->nullOnDelete();
            // Posting credits added to the organization when the invoice is paid.
            $table->unsignedInteger('credits')->default(0);
            $table->timestampsTz();
        });

        // Append-only: the balance is the sum of `delta`. Never edited or deleted.
        Schema::create('credit_ledger_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->integer('delta');
            // plan_grant | purchase | adjustment | job_published | expiry
            $table->string('reason', 16);
            $table->string('note', 500)->nullable();
            $table->foreignUlid('job_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at');

            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_ledger_entries');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('organization_subscriptions');
        Schema::dropIfExists('plans');

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recruitment_project_id');
        });

        Schema::dropIfExists('recruitment_project_members');
        Schema::dropIfExists('recruitment_projects');

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropColumn('staff_contact_opted_out_at');
        });

        Schema::dropIfExists('lead_contact_attempts');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('campaign_visits');
        Schema::dropIfExists('campaigns');
    }
};
