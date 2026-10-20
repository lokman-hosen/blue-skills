<?php

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\Consent;
use App\Domain\Config\Models\Country;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\EvidenceState;
use App\Domain\Verification\States\Evidence\IssuerVerified;
use App\Domain\Verification\States\Evidence\SelfDeclared;
use App\Domain\Verification\States\Evidence\VerificationPending;
use App\Domain\Worker\Enums\SkillSource;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;

it('belongs to a worker user and resolves countries by ISO code', function () {
    $country = Country::factory()->create(['code' => 'BD']);
    $profile = WorkerProfile::factory()->create(['current_country_code' => 'BD']);

    expect($profile->user->isWorker())->toBeTrue()
        ->and($profile->currentCountry?->is($country))->toBeTrue()
        ->and($profile->user->workerProfile?->is($profile))->toBeTrue();
});

it('records the source of each skill', function () {
    $profile = WorkerProfile::factory()->create();
    $skill = Skill::factory()->create();
    $profile->skills()->attach($skill->id, ['source' => SkillSource::Voice, 'confirmed_at' => now()]);

    $workerSkill = $profile->workerSkills()->firstOrFail();

    expect($workerSkill->source)->toBe(SkillSource::Voice)
        ->and($workerSkill->skill->is($skill))->toBeTrue();
});

it('translates taxonomy names with an English fallback', function () {
    $occupation = Occupation::factory()->create(['name' => ['en' => 'Electrician', 'bn' => 'ইলেকট্রিশিয়ান']]);

    expect($occupation->translatedName('bn'))->toBe('ইলেকট্রিশিয়ান')
        ->and($occupation->translatedName('ur'))->toBe('Electrician');
});

it('tags skills on a SkillShow', function () {
    $show = SkillShow::factory()->create();
    $show->skills()->attach(Skill::factory()->count(2)->create()->pluck('id'));

    expect($show->skills)->toHaveCount(2)
        ->and(SkillShow::MAX_DURATION_MS)->toBe(30_000);
});

it('has the six distinct evidence states from the spec', function () {
    expect(EvidenceState::all()->keys()->sort()->values()->all())->toBe([
        'expired', 'issuer_verified', 'rejected', 'self_declared', 'uploaded_unreviewed', 'verification_pending',
    ]);
});

it('verifies evidence only through review', function () {
    $evidence = EvidenceItem::factory()->create();

    expect($evidence->status)->toBeInstanceOf(SelfDeclared::class)
        ->and($evidence->status->canTransitionTo(IssuerVerified::class))->toBeFalse()
        ->and($evidence->status->canTransitionTo(VerificationPending::class))->toBeTrue();
});

it('links evidence to its document', function () {
    $document = Document::factory()->create();
    $evidence = EvidenceItem::factory()->create([
        'documentable_type' => $document->getMorphClass(),
        'documentable_id' => $document->id,
    ]);

    expect($evidence->documentable?->is($document))->toBeTrue()
        ->and($evidence->getRawOriginal('documentable_type'))->toBe('document')
        ->and($document->evidenceItems->pluck('id')->all())->toBe([$evidence->id]);
});

it('keeps revoked consents in the history', function () {
    $profile = WorkerProfile::factory()->create();
    Consent::factory()->for($profile)->create(['purpose' => ConsentPurpose::Discovery, 'revoked_at' => now()]);
    $active = Consent::factory()->for($profile)->create(['purpose' => ConsentPurpose::ApplicationShare]);

    expect($profile->consents)->toHaveCount(2)
        ->and($profile->consents()->active()->pluck('id')->all())->toBe([$active->id]);
});
