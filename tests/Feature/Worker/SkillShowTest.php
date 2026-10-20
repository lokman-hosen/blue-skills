<?php

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Models\SkillShow;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class]);
    Storage::fake('private');
    $this->worker = makeWorker();
    $this->skills = Skill::query()->whereIn('slug', ['industrial-wiring', 'db-installation'])->pluck('id')->all();
});

/**
 * A real video made with ffmpeg, so the server-side duration check runs for real.
 */
function makeVideo(int $seconds): UploadedFile
{
    $path = sys_get_temp_dir().'/skillshow-'.uniqid().'.mp4';
    Process::run([
        'ffmpeg', '-y', '-loglevel', 'error',
        '-f', 'lavfi', '-i', "testsrc=duration={$seconds}:size=320x240:rate=10",
        '-f', 'lavfi', '-i', "sine=frequency=440:duration={$seconds}",
        '-shortest', '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-c:a', 'aac', $path,
    ])->throw();

    return new UploadedFile($path, 'skillshow.mp4', 'video/mp4', null, true);
}

function hasFfmpeg(): bool
{
    return Process::run(['ffmpeg', '-version'])->successful();
}

it('checks, converts and queues a SkillShow for moderation', function () {
    $this->actingAs($this->worker)->post(route('worker.skillshow.store'), [
        'video' => makeVideo(3), 'skill_ids' => $this->skills, 'visibility' => 'employers_and_agencies',
    ])->assertRedirect(route('worker.skillshow.edit'));

    $skillShow = SkillShow::query()->firstOrFail();

    expect($skillShow->moderation_status)->toBe(ModerationStatus::Pending)
        ->and($skillShow->duration_ms)->toBeGreaterThan(2500)->toBeLessThanOrEqual(30_000)
        ->and($skillShow->getFirstMedia(SkillShow::VIDEO_COLLECTION)?->mime_type)->toBe('video/mp4')
        ->and($skillShow->getFirstMedia(SkillShow::POSTER_COLLECTION))->not->toBeNull()
        ->and($skillShow->getFirstMedia(SkillShow::UPLOAD_COLLECTION))->toBeNull()
        ->and($skillShow->skills()->count())->toBe(2);

    $this->get(route('worker.skillshow.edit'))->assertInertia(fn (Assert $page) => $page
        ->component('worker/skillshow')
        ->where('skillShow.status', 'pending')
        ->where('skillShow.videoUrl', fn (string $url) => str_contains($url, 'signature=')));
})->skip(fn () => ! hasFfmpeg(), 'ffmpeg is not installed');

it('rejects videos longer than 30 seconds by their real length', function () {
    $this->actingAs($this->worker)->post(route('worker.skillshow.store'), [
        'video' => makeVideo(32), 'skill_ids' => $this->skills, 'visibility' => 'applications_only',
    ]);

    expect(SkillShow::query()->firstOrFail())
        ->moderation_status->toBe(ModerationStatus::Failed)
        ->reason_code->toBe('too_long');
})->skip(fn () => ! hasFfmpeg(), 'ffmpeg is not installed');

it('marks unreadable files as failed and validates the form', function () {
    $this->actingAs($this->worker)->post(route('worker.skillshow.store'), [
        'video' => UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4'),
        'skill_ids' => $this->skills,
        'visibility' => 'hidden',
    ]);

    expect(SkillShow::query()->firstOrFail())
        ->moderation_status->toBe(ModerationStatus::Failed)
        ->reason_code->toBe('unreadable');

    $this->post(route('worker.skillshow.store'), [
        'video' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        'skill_ids' => [],
        'visibility' => 'everyone',
    ])->assertSessionHasErrors(['video', 'skill_ids', 'visibility']);
});

it('lets only the worker change or delete their SkillShow', function () {
    $skillShow = SkillShow::factory()->for($this->worker->workerProfile)->create();

    $this->actingAs(makeWorker())->put(route('worker.skillshow.update', $skillShow), [
        'skill_ids' => $this->skills, 'visibility' => 'hidden',
    ])->assertForbidden();
    $this->delete(route('worker.skillshow.destroy', $skillShow))->assertForbidden();

    $this->actingAs($this->worker)->put(route('worker.skillshow.update', $skillShow), [
        'skill_ids' => $this->skills, 'visibility' => 'hidden',
    ])->assertRedirect(route('worker.skillshow.edit'));

    // New tags and visibility keep the moderation decision: the video did not change.
    expect($skillShow->refresh()->visibility->value)->toBe('hidden')
        ->and($skillShow->moderation_status)->toBe(ModerationStatus::Pending);

    $this->delete(route('worker.skillshow.destroy', $skillShow))->assertRedirect();
    expect(SkillShow::query()->count())->toBe(0);
});

it('lets moderators publish or reject with a reason', function () {
    $moderator = User::factory()->staff()->withTwoFactor()->create()->assignStaffRole(StaffRole::VerificationOfficer);
    [$first, $second] = SkillShow::factory()->count(2)->for($this->worker->workerProfile)->create();

    $this->actingAs($moderator)->get(route('admin.skillshows.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/skillshows')->has('queue', 2)->has('reasons', 5));

    $this->patch(route('admin.skillshows.update', $first), ['decision' => 'approve'])->assertRedirect(route('admin.skillshows.index'));
    $this->patch(route('admin.skillshows.update', $second), ['decision' => 'reject'])->assertSessionHasErrors('reason');
    $this->patch(route('admin.skillshows.update', $second), ['decision' => 'reject', 'reason' => 'poor_quality'])->assertRedirect();

    expect($first->refresh())
        ->moderation_status->toBe(ModerationStatus::Approved)
        ->moderated_by->toBe($moderator->id)
        ->and($second->refresh())
        ->moderation_status->toBe(ModerationStatus::Rejected)
        ->reason_code->toBe('poor_quality');

    // A decision is final until the worker sends a new video.
    $this->patch(route('admin.skillshows.update', $first), ['decision' => 'reject', 'reason' => 'inappropriate'])
        ->assertSessionHasErrors('decision');

    $logged = Activity::query()->where('description', 'skill_show.rejected')->firstOrFail();
    expect($logged->causer_id)->toBe($moderator->id)
        ->and($logged->properties['reason_code'])->toBe('poor_quality')
        ->and($logged->properties['old']['moderation_status'])->toBe('pending');
});

it('keeps moderation to staff with the permission', function () {
    $skillShow = SkillShow::factory()->for($this->worker->workerProfile)->create();
    $crm = User::factory()->staff()->withTwoFactor()->create()->assignStaffRole(StaffRole::CrmAgent);

    $this->actingAs($crm)->get(route('admin.skillshows.index'))->assertForbidden();
    $this->actingAs($crm)->patch(route('admin.skillshows.update', $skillShow), ['decision' => 'approve'])->assertForbidden();
    $this->actingAs($this->worker)->get(route('admin.skillshows.index'))->assertForbidden();

    expect($skillShow->refresh()->moderation_status)->toBe(ModerationStatus::Pending);
});
