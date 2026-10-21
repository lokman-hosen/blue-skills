<?php

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\Application;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\States\Evidence\UploadedUnreviewed;
use Database\Seeders\CountrySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CountrySeeder::class);
    Storage::fake('private');
    $this->worker = makeWorker();
});

function scanFile(): UploadedFile
{
    return UploadedFile::fake()->create('passport.pdf', 200, 'application/pdf');
}

it('uploads a passport scan as unreviewed private evidence', function () {
    $this->actingAs($this->worker)
        ->post(route('worker.documents.store'), ['type' => 'passport', 'expires_on' => now()->addYears(3)->toDateString(), 'document' => scanFile()])
        ->assertRedirect(route('worker.documents.index'));

    $document = Document::query()->sole();
    expect($document->type)->toBe(DocumentType::Passport)
        ->and($document->getFirstMedia(Document::FILE_COLLECTION)?->disk)->toBe('private')
        ->and($document->evidenceItems()->sole()->status)->toBeInstanceOf(UploadedUnreviewed::class);

    $this->get(route('worker.documents.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('worker/documents')
            ->has('documents', 1)
            ->where('documents.0.state', 'uploaded_unreviewed')
            ->where('documents.0.sharedWith', []));
});

it('validates the upload', function () {
    $this->actingAs($this->worker)
        ->post(route('worker.documents.store'), [
            'type' => 'certificate',
            'expires_on' => now()->subDay()->toDateString(),
            'document' => UploadedFile::fake()->create('scan.exe', 10, 'application/x-msdownload'),
        ])
        ->assertSessionHasErrors(['type', 'expires_on', 'document']);
});

it('replaces the scan and stops sharing the old one', function () {
    $this->actingAs($this->worker)->post(route('worker.documents.store'), ['type' => 'passport', 'document' => scanFile()]);
    $old = Document::query()->sole();
    $application = Application::factory()->create(['worker_profile_id' => $this->worker->workerProfile->id]);
    $application->consents()->create(['item' => ApplicationConsentItem::PassportScan, 'granted' => true, 'granted_at' => now(), 'document_id' => $old->id]);
    $this->worker->workerProfile->consents()->create([
        'purpose' => ConsentPurpose::DocumentShare, 'subject_type' => 'application', 'subject_id' => $application->id,
        'scope' => ['item' => 'passport_scan', 'document' => $old->id], 'granted_at' => now(),
    ]);

    $this->post(route('worker.documents.store'), ['type' => 'passport', 'document' => scanFile()]);

    expect(Document::query()->count())->toBe(1)
        ->and(Document::withTrashed()->find($old->id)?->trashed())->toBeTrue()
        ->and($application->consents()->active()->count())->toBe(0)
        ->and($this->worker->workerProfile->consents()->active()->count())->toBe(0);
});

it('deletes only the worker\'s own documents', function () {
    $this->actingAs($this->worker)->post(route('worker.documents.store'), ['type' => 'national_id', 'document' => scanFile()]);
    $document = Document::query()->sole();

    $this->actingAs(makeWorker())->delete(route('worker.documents.destroy', $document))->assertNotFound();
    $this->actingAs($this->worker)->delete(route('worker.documents.destroy', $document))->assertRedirect();

    expect(Document::query()->count())->toBe(0);
});
