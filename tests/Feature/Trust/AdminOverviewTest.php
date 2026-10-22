<?php

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Trust\Models\Report;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\States\VerificationRequest\Open;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
});

it('shows each staff member the queues they can work on and the marketplace', function () {
    $job = JobPosting::factory()->published()->create();
    $job->verificationRequests()->create(['type' => VerificationType::Job, 'status' => Open::class]);
    Report::factory()->create(['subject_type' => 'job', 'subject_id' => $job->id]);

    $this->actingAs(staffUser(StaffRole::Superuser))->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/overview')
            ->has('overview.queues', 10)
            ->where('overview.marketplace.live_jobs', 1)
            ->where('overview.corridorsPendingLegalReview', fn ($count) => $count > 0));

    $this->actingAs(staffUser(StaffRole::TrustOfficer))->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('overview.queues', [['key' => 'jobs', 'count' => 1], ['key' => 'reports', 'count' => 1]]));

    // CRM agents see only their lead follow-ups (Module 9).
    $this->actingAs(staffUser(StaffRole::CrmAgent))->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('overview.queues', [['key' => 'leads_due', 'count' => 0]]));
});
