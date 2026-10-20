<?php

use App\Domain\Jobs\Data\JobTruthCardData;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;

function checkKeys(JobTruthCardData $card): array
{
    return array_map(fn ($check) => $check->key, $card->checks);
}

it('keeps undisclosed values null instead of guessing', function () {
    $job = JobPosting::factory()->create([
        'salary_amount_minor' => null,
        'salary_currency' => null,
        'food' => null,
        'overtime_terms' => null,
        'visa_payer' => null,
    ]);

    $card = JobTruthCardData::fromJob($job);
    $food = collect($card->benefits)->firstWhere('key', 'food');

    expect($card->salary)->toBeNull()
        ->and($card->overtimeTerms)->toBeNull()
        ->and($card->visaPayer)->toBeNull()
        ->and($food?->provision)->toBeNull()
        ->and(checkKeys($card))->not->toContain('salary_employer_declared');
});

it('only claims checks that were performed', function () {
    $unverified = JobTruthCardData::fromJob(JobPosting::factory()->create());
    $verified = JobTruthCardData::fromJob(JobPosting::factory()->published()->create([
        'employer_organization_id' => Organization::factory()->verified(),
    ]));

    expect(checkKeys($unverified))->toContain('employer_not_verified', 'job_not_reviewed')
        ->not->toContain('employer_verified', 'job_reviewed')
        ->and(checkKeys($verified))->toContain('employer_verified', 'job_reviewed', 'salary_employer_declared');
});

it('reports agency licence and mandate status for agency postings', function () {
    $agency = Organization::factory()->agency()->verified()->create(['licence_expires_on' => now()->subDay()]);
    $mandate = JobMandate::factory()->create(['agency_organization_id' => $agency->id, 'status' => MandateStatus::Pending]);
    $job = JobPosting::factory()->create([
        'organization_id' => $agency->id,
        'employer_organization_id' => $mandate->employer_organization_id,
        'agency_organization_id' => $agency->id,
        'job_mandate_id' => $mandate->id,
    ]);

    $card = JobTruthCardData::fromJob($job);

    expect($card->postedByAgency)->toBeTrue()
        ->and(collect($card->checks)->firstWhere('key', 'agency_licence_expired')?->tone)->toBe('bad')
        ->and(collect($card->checks)->firstWhere('key', 'mandate_pending')?->tone)->toBe('warn');
});

it('itemises costs with who pays', function () {
    $job = JobPosting::factory()->create();
    JobCost::factory()->for($job, 'job')->create(['payer' => CostPayer::Worker, 'amount_minor' => 8000, 'currency' => 'EUR']);

    $card = JobTruthCardData::fromJob($job);

    expect($card->costs)->toHaveCount(1)
        ->and($card->costs[0]->payer)->toBe(CostPayer::Worker)
        ->and($card->costs[0]->amount->amountMinor)->toBe(8000)
        ->and($card->costs[0]->amount->currency)->toBe('EUR');
});
