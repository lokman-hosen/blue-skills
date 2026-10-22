<?php

use App\Domain\Billing\Enums\CreditReason;
use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Billing\Models\OrganizationSubscription;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Support\BillingLimits;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Submitted;
use App\Domain\Organization\Actions\InviteOrganizationMember;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Enums\VerificationType;
use Database\Seeders\CountryRuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([CountrySeeder::class, CountryRuleSeeder::class, LanguageSeeder::class, RolesAndPermissionsSeeder::class]);
    Mail::fake();
    $this->employer = Organization::factory()->employer()->verified()->create();
    $this->owner = orgMember($this->employer, OrganizationRole::Owner);
});

it('blocks nothing while enforcement is off', function () {
    $job = JobPosting::factory()->create(['organization_id' => $this->employer->id]);

    BillingLimits::ensureCanSubmit($job);
    BillingLimits::ensureSeatAvailable($this->employer);

    expect(BillingLimits::allowsTalentSearch($this->employer))->toBeTrue();
    $this->actingAs($this->owner)->get(route('org.talent.index'))->assertOk();
});

it('needs a credit to submit a new job and uses it when the job is published', function () {
    config(['blueskills.billing.enforce' => true]);
    $job = JobPosting::factory()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id, 'destination_country_code' => 'AE']);

    expect(fn () => BillingLimits::ensureCanSubmit($job))->toThrow(ValidationException::class);

    CreditLedgerEntry::query()->create(['organization_id' => $this->employer->id, 'delta' => 1, 'reason' => CreditReason::Adjustment]);
    BillingLimits::ensureCanSubmit($job->fresh());

    $job->update(['status' => Submitted::class, 'submitted_at' => now()]);
    $review = $job->verificationRequests()->create(['type' => VerificationType::Job]);
    $this->actingAs(staffUser(StaffRole::TrustOfficer))->patch(route('admin.jobs.decide', $review), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    expect($this->employer->creditBalance())->toBe(0)
        ->and(CreditLedgerEntry::query()->where('reason', CreditReason::JobPublished)->value('job_id'))->toBe($job->id);

    // Resubmitting a live job after a material change costs nothing.
    BillingLimits::ensureCanSubmit($job->fresh());
});

it('limits seats and talent search to the plan', function () {
    config(['blueskills.billing.enforce' => true]);
    $plan = Plan::factory()->create(['seats' => 1, 'talent_search' => false]);
    OrganizationSubscription::factory()->create(['organization_id' => $this->employer->id, 'plan_id' => $plan->id]);

    expect(fn () => app(InviteOrganizationMember::class)->handle($this->employer, $this->owner, 'new@example.test', OrganizationRole::Recruiter))
        ->toThrow(ValidationException::class);
    $this->actingAs($this->owner)->get(route('org.talent.index'))->assertForbidden();

    $plan->update(['seats' => 5, 'talent_search' => true]);
    app(InviteOrganizationMember::class)->handle($this->employer->fresh(), $this->owner, 'new@example.test', OrganizationRole::Recruiter);
    $this->actingAs($this->owner)->get(route('org.talent.index'))->assertOk();
});
