<?php

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\Expired;
use App\Domain\Verification\States\Evidence\IssuerVerified;
use App\Domain\Verification\States\Evidence\Rejected;
use App\Domain\Verification\States\Evidence\UploadedUnreviewed;
use App\Domain\Verification\States\Evidence\VerificationPending;
use App\Domain\Worker\Support\MediaUrls;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    Storage::fake('private');
    $this->officer = staffUser(StaffRole::VerificationOfficer);
    $this->worker = makeWorker();
    $this->actingAs($this->worker)->post(route('worker.passport.evidence.store'), [
        'type' => 'certificate', 'title' => 'Electrical training', 'document' => UploadedFile::fake()->createWithContent('cert.pdf', "%PDF-1.4\n%%EOF"),
    ]);
    $this->item = EvidenceItem::query()->sole();
});

it('opens a worker file only after the review starts, and logs every view', function () {
    $url = MediaUrls::signed($this->item->documentable?->getFirstMedia(Document::FILE_COLLECTION));

    $this->actingAs($this->officer)->get(route('admin.evidence.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/evidence/index')->where('items.data.0.id', $this->item->id));
    $this->get(route('admin.evidence.show', $this->item))
        ->assertInertia(fn (Assert $page) => $page->where('item.fileUrl', null)->where('canDecide', false));
    $this->get($url)->assertForbidden();

    $this->post(route('admin.evidence.start', $this->item))->assertRedirect(route('admin.evidence.show', $this->item));
    expect($this->item->fresh()?->status->equals(VerificationPending::class))->toBeTrue();
    $this->get(route('admin.evidence.show', $this->item))
        ->assertInertia(fn (Assert $page) => $page->whereNot('item.fileUrl', null)->where('item.reviewer', $this->officer->name));
    $this->get($url)->assertOk();

    expect(Activity::query()->where('description', 'document.viewed_by_staff')->where('causer_id', $this->officer->id)->count())->toBe(1);

    // Trust officers don't review documents.
    $this->actingAs(staffUser(StaffRole::TrustOfficer))->get(route('admin.evidence.index'))->assertForbidden();
    $this->get($url)->assertForbidden();
});

it('verifies or rejects with a reason the worker is told', function () {
    $this->actingAs($this->officer)->patch(route('admin.evidence.decide', $this->item), ['decision' => 'approved'])->assertSessionHasErrors('decision');
    $this->post(route('admin.evidence.start', $this->item));

    $this->patch(route('admin.evidence.decide', $this->item), ['decision' => 'rejected'])->assertSessionHasErrors('reason');
    $this->patch(route('admin.evidence.decide', $this->item), ['decision' => 'needs_info', 'reason' => 'document_unreadable'])->assertSessionHasErrors('decision');
    $this->patch(route('admin.evidence.decide', $this->item), ['decision' => 'rejected', 'reason' => 'document_unreadable', 'note' => 'The stamp is cut off.'])
        ->assertRedirect(route('admin.evidence.index'));

    expect($this->item->fresh()?->status->equals(Rejected::class))->toBeTrue()
        ->and($this->item->fresh()?->reason_code)->toBe('document_unreadable')
        ->and($this->worker->notifications()->pluck('type')->all())->toContain('evidence_reviewed');
    $this->get(route('admin.evidence.index'))->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
});

it('marks verified evidence expired once its document expires, and leaves institute records out of the queue', function () {
    $this->item->status->transitionTo(VerificationPending::class);
    $this->item->status->transitionTo(IssuerVerified::class);
    $this->item->documentable?->update(['expires_at' => now()->subDay()]);

    Artisan::call('evidence:expire');
    expect($this->item->fresh()?->status->equals(Expired::class))->toBeTrue();

    $attestation = TrainingAttestation::factory()->create();
    $attestation->evidence()->create(['worker_profile_id' => $attestation->worker_profile_id, 'type' => EvidenceType::Training, 'status' => UploadedUnreviewed::class]);
    $this->actingAs($this->officer)->get(route('admin.evidence.index'))->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
});
