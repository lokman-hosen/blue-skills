<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Mobility\Actions\OpenMobilityCase;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Support\MediaUrls;
use Database\Seeders\CountryRuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, CountryRuleSeeder::class, RolesAndPermissionsSeeder::class]);
    Storage::fake('private');
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->employerHr = orgMember($this->employer, OrganizationRole::HrAdmin);
    $this->agency = Organization::factory()->agency()->verified()->create(['display_name' => 'Northstar', 'licence_countries' => ['AE']]);
    $this->officer = orgMember($this->agency, OrganizationRole::CaseOfficer);
    $this->worker = makeWorker();
    $this->case = agencyCase($this->worker);
});

/** An open case on an agency job to the UAE, run by the agency for ABC Contracting. */
function agencyCase(User $worker): MobilityCase
{
    $mandate = JobMandate::factory()->create(['agency_organization_id' => test()->agency->id, 'employer_organization_id' => test()->employer->id, 'status' => MandateStatus::Verified]);
    $application = Application::factory()->create([
        'job_id' => JobPosting::factory()->published()->create([
            'organization_id' => test()->agency->id, 'agency_organization_id' => test()->agency->id, 'job_mandate_id' => $mandate->id,
            'employer_organization_id' => test()->employer->id, 'destination_country_code' => 'AE',
        ]),
        'worker_profile_id' => $worker->workerProfile->id,
    ]);

    return app(OpenMobilityCase::class)->handle($application, test()->officer);
}

function caseItem(MobilityCase $case, DocumentType $type): CaseDocument
{
    return $case->documents()->where('type', $type)->firstOrFail();
}

function pdf(string $name = 'file.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n%%EOF");
}

it('opens the case with the destination checklist', function () {
    $items = $this->case->documents()->get();

    expect($items->pluck('type')->all())->toBe([DocumentType::Passport, DocumentType::Photo, DocumentType::Medical, DocumentType::Contract])
        ->and($items->every(fn (CaseDocument $item) => $item->status === CaseDocumentStatus::Requested))->toBeTrue()
        ->and(caseItem($this->case, DocumentType::Contract)->provided_by)->toBe(DocumentProvider::Organization)
        ->and(caseItem($this->case, DocumentType::Passport)->employer_can_view)->toBeFalse()
        ->and(caseItem($this->case, DocumentType::Medical)->employer_can_view)->toBeTrue();

    $this->actingAs($this->worker)->get(route('worker.cases.show', $this->case))
        ->assertInertia(fn (Assert $page) => $page->has('documents', 4)
            ->where('documents.0.title', 'Passport copy')
            ->where('documents.0.status.value', 'doc_requested')
            ->where('documents.0.visibleTo', [])
            ->where('canRespond', true));
});

it('shares a worker file per item and stops at once when the worker revokes it', function () {
    $medical = caseItem($this->case, DocumentType::Medical);

    $this->actingAs($this->worker)
        ->post(route('worker.cases.documents.share', [$this->case, $medical]), ['document' => pdf('medical.pdf'), 'employer_can_view' => '0'])
        ->assertRedirect(route('worker.cases.show', $this->case));

    $medical->refresh();
    $url = MediaUrls::signed($medical->document?->getFirstMedia(Document::FILE_COLLECTION));
    expect($medical->status)->toBe(CaseDocumentStatus::Uploaded)
        ->and($medical->isShared())->toBeTrue()
        ->and($medical->employer_can_view)->toBeFalse()
        ->and($this->officer->notifications()->pluck('type')->all())->toContain('case_document_answered');

    // The agency sees it; the employer doesn't until the worker says so.
    actingInOrg($this->officer, $this->agency)->get(route('org.cases.show', $this->case))
        ->assertInertia(fn (Assert $page) => $page->where('documents.2.visibleTo', ['Northstar'])->whereNot('documents.2.fileUrl', null));
    $this->get($url)->assertOk();
    actingInOrg($this->employerHr, $this->employer)->get(route('org.cases.show', $this->case))
        ->assertInertia(fn (Assert $page) => $page->has('documents', 2)->where('documents.0.type', 'photo')
            // Nor does its history mention the medical file (D-081).
            ->where('mobilityCase.events', fn ($events) => collect($events)->every(fn ($event) => $event['document'] !== 'Medical certificate')));
    $this->get($url)->assertForbidden();

    $this->actingAs($this->worker)->put(route('worker.cases.documents.visibility', [$this->case, $medical]), ['employer_can_view' => true])->assertRedirect();
    actingInOrg($this->employerHr, $this->employer)->get($url)->assertOk();

    $this->actingAs($this->worker)->delete(route('worker.cases.documents.revoke', [$this->case, $medical]))->assertRedirect();
    expect($medical->fresh()?->status)->toBe(CaseDocumentStatus::Requested)
        ->and($this->case->events()->where('action', CaseEventAction::DocumentRevoked)->exists())->toBeTrue();
    actingInOrg($this->officer, $this->agency)->get($url)->assertForbidden();
    actingInOrg($this->employerHr, $this->employer)->get($url)->assertForbidden();
    $this->actingAs($this->worker)->get($url)->assertOk();
    actingInOrg($this->officer, $this->agency)->get(route('org.cases.show', $this->case))
        ->assertInertia(fn (Assert $page) => $page->where('documents.2.status.value', 'doc_revoked')->where('documents.2.fileUrl', null));
});

it('shares the passport scan on file and revokes the share when the scan is deleted', function () {
    $this->actingAs($this->worker)->post(route('worker.documents.store'), ['type' => 'passport', 'document' => pdf('passport.pdf')]);
    $scan = Document::query()->where('type', DocumentType::Passport)->sole();
    $passport = caseItem($this->case, DocumentType::Passport);

    $this->get(route('worker.cases.show', $this->case))->assertInertia(fn (Assert $page) => $page->where('scansOnFile.0.id', $scan->id));
    $this->post(route('worker.cases.documents.share', [$this->case, $passport]), ['document_id' => $scan->id])->assertRedirect();
    expect($passport->fresh()?->document_id)->toBe($scan->id);

    $this->delete(route('worker.documents.destroy', $scan))->assertRedirect();
    expect($passport->fresh()?->isShared())->toBeFalse()
        ->and($passport->fresh()?->status)->toBe(CaseDocumentStatus::Requested);
});

it('lets the managing organization accept or send back a shared file with a reason', function () {
    $medical = caseItem($this->case, DocumentType::Medical);
    $this->actingAs($this->worker)->post(route('worker.cases.documents.share', [$this->case, $medical]), ['document' => pdf()]);

    actingInOrg($this->officer, $this->agency)
        ->post(route('org.cases.documents.decide', [$this->case, $medical, 'return']), [])
        ->assertSessionHasErrors('reason');
    $this->post(route('org.cases.documents.decide', [$this->case, $medical, 'return']), ['reason' => 'other'])
        ->assertSessionHasErrors('note');
    $this->post(route('org.cases.documents.decide', [$this->case, $medical, 'return']), ['reason' => 'unreadable', 'note' => 'The stamp is cut off.'])
        ->assertRedirect();

    expect($medical->fresh()?->status)->toBe(CaseDocumentStatus::Returned)
        ->and($medical->fresh()?->reason_code)->toBe('unreadable')
        ->and($this->worker->notifications()->pluck('type')->all())->toContain('case_document');

    // A new file goes back for a check, then is accepted.
    $this->actingAs($this->worker)->post(route('worker.cases.documents.share', [$this->case, $medical]), ['document' => pdf('medical-2.pdf')]);
    actingInOrg($this->officer, $this->agency)->post(route('org.cases.documents.decide', [$this->case, $medical, 'accept']))->assertRedirect();
    expect($medical->fresh()?->status)->toBe(CaseDocumentStatus::Accepted)
        ->and(Document::query()->where('type', DocumentType::Medical)->count())->toBe(1);

    // An accepted file can't be replaced.
    $this->actingAs($this->worker)->post(route('worker.cases.documents.share', [$this->case, $medical]), ['document' => pdf()])
        ->assertSessionHasErrors('document');
});

it('sends the contract to the worker to confirm or query', function () {
    $contract = caseItem($this->case, DocumentType::Contract);

    actingInOrg($this->officer, $this->agency)
        ->post(route('org.cases.documents.upload', [$this->case, $contract]), ['document' => pdf('contract.pdf'), 'employer_can_view' => '1'])
        ->assertRedirect();
    expect($contract->fresh()?->status)->toBe(CaseDocumentStatus::Uploaded);

    $this->actingAs($this->worker)->get(route('worker.cases.show', $this->case))
        ->assertInertia(fn (Assert $page) => $page->where('documents.3.status.value', 'doc_contract_sent')->whereNot('documents.3.fileUrl', null));
    $this->post(route('worker.cases.documents.contract', [$this->case, $contract, 'query']))->assertSessionHasErrors('note');
    $this->post(route('worker.cases.documents.contract', [$this->case, $contract, 'query']), ['note' => 'Overtime rate is missing.'])->assertRedirect();
    expect($contract->fresh()?->status)->toBe(CaseDocumentStatus::Returned);

    actingInOrg($this->officer, $this->agency)->post(route('org.cases.documents.upload', [$this->case, $contract]), ['document' => pdf('contract-v2.pdf')]);
    $this->actingAs($this->worker)->post(route('worker.cases.documents.contract', [$this->case, $contract, 'confirm']))->assertRedirect();

    expect($contract->fresh()?->status)->toBe(CaseDocumentStatus::Accepted)
        ->and($contract->fresh()?->decided_by)->toBe($this->worker->id)
        ->and($this->case->events()->where('action', CaseEventAction::ContractConfirmed)->exists())->toBeTrue()
        ->and(Document::query()->where('type', DocumentType::Contract)->count())->toBe(1);
});

it('lets the managing organization ask for more documents', function () {
    actingInOrg($this->officer, $this->agency)
        ->post(route('org.cases.documents.store', $this->case), ['type' => 'trade_licence', 'provided_by' => 'worker'])
        ->assertSessionHasErrors('type');
    $this->post(route('org.cases.documents.store', $this->case), ['type' => 'police_clearance', 'provided_by' => 'worker', 'due_on' => now()->addWeek()->toDateString(), 'note' => 'From the Dhaka metropolitan police.'])
        ->assertRedirect();

    expect(caseItem($this->case, DocumentType::PoliceClearance)->title)->toBe('Police clearance')
        ->and($this->worker->notifications()->pluck('type')->all())->toContain('case_document');
});

it('keeps the checklist to the managing organization and the case\'s worker', function () {
    $medical = caseItem($this->case, DocumentType::Medical);
    $contract = caseItem($this->case, DocumentType::Contract);

    // The employer and a compliance reviewer follow but can't change the checklist.
    actingInOrg($this->employerHr, $this->employer)->post(route('org.cases.documents.store', $this->case), ['type' => 'visa', 'provided_by' => 'organization'])->assertNotFound();
    $reviewer = orgMember($this->agency, OrganizationRole::ComplianceReviewer);
    actingInOrg($reviewer, $this->agency)->post(route('org.cases.documents.upload', [$this->case, $contract]), ['document' => pdf()])->assertForbidden();

    // Another worker can't share into this case; items from another case don't bind.
    $this->actingAs(makeWorker())->post(route('worker.cases.documents.share', [$this->case, $medical]), ['document' => pdf()])->assertForbidden();
    $other = agencyCase($otherWorker = makeWorker());
    $this->actingAs($this->worker)->post(route('worker.cases.documents.share', [$this->case, caseItem($other, DocumentType::Medical)]), ['document' => pdf()])->assertNotFound();

    // Workers can't upload the organization's documents, nor check their own.
    $this->post(route('worker.cases.documents.share', [$this->case, $contract]), ['document' => pdf()])->assertSessionHasErrors('document');
    $this->post(route('worker.cases.documents.share', [$this->case, $medical]), [])->assertSessionHasErrors('document');
    $this->post(route('worker.cases.documents.share', [$this->case, $medical]), ['document' => UploadedFile::fake()->create('medical.exe', 10, 'application/x-msdownload')])->assertSessionHasErrors('document');
    $this->post(route('org.cases.documents.decide', [$this->case, $medical, 'accept']))->assertForbidden();
    expect($otherWorker->id)->not->toBe($this->worker->id);
});
