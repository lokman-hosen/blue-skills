<?php

use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Worker\Actions\PruneVoiceRecordings;
use App\Domain\Worker\Contracts\ProfileExtractor;
use App\Domain\Worker\Contracts\SpeechToText;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\SkillSource;
use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Models\VoiceAnswer;
use App\Domain\Worker\Voice\UnavailableSpeechToText;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class]);
    Storage::fake('private');
    $this->worker = makeWorker();
});

/** The confirmed fields for all seven questions, in the request shape. */
function confirmedFields(): array
{
    return [
        'occupation' => ['occupation' => 'electrician', 'specialization' => 'industrial-electrician'],
        'experience' => ['years_experience' => 8],
        'countries' => ['countries' => [['code' => 'BD', 'years' => 5], ['code' => 'AE', 'years' => 3]]],
        'skills' => ['skills' => ['industrial-wiring', 'db-installation', 'maintenance']],
        'passport' => ['passport_status' => 'ready', 'passport_valid_until_year' => 2030],
        'availability' => ['availability' => 'immediately'],
        'destinations' => ['destinations' => ['AE', 'PL']],
    ];
}

function fakeRecording(): UploadedFile
{
    return UploadedFile::fake()->create('answer.webm', 40, 'audio/webm');
}

it('reads what the worker means from typed answers in English and Bangla', function (string $question, string $locale, string $text, array $expected) {
    $occupationId = Occupation::query()->where('slug', 'electrician')->value('id');

    expect(app(ProfileExtractor::class)->extract(VoiceQuestion::from($question), $text, $locale, $occupationId))->toBe($expected);
})->with([
    'occupation en' => ['occupation', 'en', 'I am an electrician. I mostly do industrial wiring.', ['occupation' => 'electrician', 'specialization' => 'industrial-electrician']],
    'occupation bn' => ['occupation', 'bn', 'আমি ইলেকট্রিশিয়ান, বেশিরভাগ ইন্ডাস্ট্রিয়াল ওয়্যারিং করি।', ['occupation' => 'electrician', 'specialization' => 'industrial-electrician']],
    'experience words' => ['experience', 'en', 'Eight years.', ['years_experience' => 8]],
    'experience bn' => ['experience', 'bn', 'আট বছর।', ['years_experience' => 8]],
    'countries en' => ['countries', 'en', 'Five years in Bangladesh and three years in Dubai.', ['countries' => [['code' => 'BD', 'years' => 5], ['code' => 'AE', 'years' => 3]]]],
    'countries bn' => ['countries', 'bn', 'বাংলাদেশে পাঁচ বছর, দুবাইতে তিন বছর।', ['countries' => [['code' => 'BD', 'years' => 5], ['code' => 'AE', 'years' => 3]]]],
    'skills en' => ['skills', 'en', 'Industrial wiring, DB installation and maintenance.', ['skills' => ['industrial-wiring', 'db-installation', 'maintenance']]],
    'skills bn' => ['skills', 'bn', 'ইন্ডাস্ট্রিয়াল ওয়্যারিং, ডিবি ইনস্টলেশন আর মেইনটেন্যান্স।', ['skills' => ['industrial-wiring', 'db-installation', 'maintenance']]],
    'passport en' => ['passport', 'en', 'Yes, I have a passport. It is valid until 2030.', ['passport_status' => 'ready', 'passport_valid_until_year' => 2030]],
    'passport bn digits' => ['passport', 'bn', 'হ্যাঁ, পাসপোর্ট আছে, মেয়াদ ২০৩০ পর্যন্ত।', ['passport_status' => 'ready', 'passport_valid_until_year' => 2030]],
    'no passport' => ['passport', 'en', 'No, I do not have one.', ['passport_status' => 'none', 'passport_valid_until_year' => null]],
    'availability now' => ['availability', 'bn', 'এখনই শুরু করতে পারব।', ['availability' => 'immediately']],
    'availability months' => ['availability', 'en', 'In two months.', ['availability' => 'within_60_days']],
    'destinations' => ['destinations', 'en', 'Dubai, or Poland.', ['destinations' => ['AE', 'PL']]],
    // Never a guess: unclear text gives empty fields.
    'nothing clear' => ['experience', 'en', 'Quite a long time.', ['years_experience' => null]],
]);

it('turns a typed answer into suggested fields without touching the profile', function () {
    $this->actingAs($this->worker)
        ->post(route('worker.voice.submit', 'experience'), ['text' => 'Eight years.'])
        ->assertRedirect(route('worker.voice.show', ['q' => 'experience']));

    $answer = VoiceAnswer::query()->firstOrFail();

    expect($answer->status)->toBe(VoiceAnswerStatus::Extracted)
        ->and($answer->typed)->toBeTrue()
        ->and($answer->extracted)->toBe(['years_experience' => 8])
        ->and($this->worker->workerProfile->refresh()->years_experience)->toBeNull();

    $this->get(route('worker.voice.show', ['q' => 'experience']))->assertInertia(fn (Assert $page) => $page
        ->component('worker/voice/question')
        ->where('question', 'experience')
        ->where('answer.status', 'extracted')
        ->where('answer.fields.yearsExperience', 8)
        ->where('voiceAvailable', true));
});

it('transcribes a recording, then deletes the recording once confirmed', function () {
    $this->actingAs($this->worker)->post(route('worker.voice.submit', 'countries'), ['audio' => fakeRecording()]);

    $answer = VoiceAnswer::query()->firstOrFail();
    $media = $answer->getFirstMedia(VoiceAnswer::AUDIO_COLLECTION);

    // The sample transcriber answers in the worker's language (bn for factory workers).
    expect($answer->status)->toBe(VoiceAnswerStatus::Extracted)
        ->and($answer->locale)->toBe(app()->getLocale())
        ->and($answer->transcript)->not->toBeEmpty()
        ->and($answer->extracted)->toBe(['countries' => [['code' => 'BD', 'years' => 5], ['code' => 'AE', 'years' => 3]]])
        ->and($media?->disk)->toBe('private');
    Storage::disk('private')->assertExists($media->getPathRelativeToRoot());

    $this->post(route('worker.voice.confirm', 'countries'), ['answer_id' => $answer->id, ...confirmedFields()['countries']])
        ->assertRedirect(route('worker.voice.show', ['q' => 'occupation']));

    expect($answer->refresh()->status)->toBe(VoiceAnswerStatus::Confirmed)
        ->and($answer->getFirstMedia(VoiceAnswer::AUDIO_COLLECTION))->toBeNull();
    Storage::disk('private')->assertMissing($media->getPathRelativeToRoot());
});

it('records a failure and offers typing when no speech provider is configured', function () {
    $this->app->bind(SpeechToText::class, UnavailableSpeechToText::class);

    $this->actingAs($this->worker)->post(route('worker.voice.submit', 'skills'), ['audio' => fakeRecording()]);

    expect(VoiceAnswer::query()->firstOrFail())
        ->status->toBe(VoiceAnswerStatus::Failed)
        ->error_code->toBe('unavailable');

    $this->get(route('worker.voice.show'))->assertInertia(fn (Assert $page) => $page->where('voiceAvailable', false));
});

it('saves only after all seven answers are confirmed, then fills the Skill Passport', function () {
    $this->actingAs($this->worker);

    foreach (array_slice(confirmedFields(), 0, 6) as $question => $fields) {
        $this->post(route('worker.voice.confirm', $question), $fields)->assertSessionHasNoErrors();
    }

    $this->post(route('worker.voice.apply'))->assertSessionHasErrors('answers');
    expect($this->worker->workerProfile->refresh()->occupation_id)->toBeNull();

    $this->post(route('worker.voice.confirm', 'destinations'), confirmedFields()['destinations'])
        ->assertRedirect(route('worker.voice.review'));

    $this->get(route('worker.voice.review'))->assertInertia(fn (Assert $page) => $page
        ->component('worker/voice/review')
        ->has('answers', 7));

    $this->post(route('worker.voice.apply'))->assertRedirect(route('worker.passport.show'));

    $profile = $this->worker->workerProfile->refresh()->load(['occupation', 'specialization', 'experiences', 'skills', 'destinationPreferences']);

    expect($profile->occupation->slug)->toBe('electrician')
        ->and($profile->specialization->slug)->toBe('industrial-electrician')
        ->and($profile->years_experience)->toBe(8)
        ->and($profile->passport_status)->toBe(PassportStatus::Ready)
        ->and($profile->passport_valid_until_year)->toBe(2030)
        ->and($profile->availability)->toBe(Availability::Immediately)
        ->and($profile->experiences->map(fn ($e) => [$e->country_code, $e->years])->all())->toEqualCanonicalizing([['BD', 5], ['AE', 3]])
        ->and($profile->skills->pluck('slug')->all())->toEqualCanonicalizing(['industrial-wiring', 'db-installation', 'maintenance'])
        ->and($profile->workerSkills()->first()->source)->toBe(SkillSource::Typed)
        ->and($profile->destinationPreferences->pluck('country_code')->all())->toBe(['AE', 'PL'])
        ->and($profile->profile_completed_at)->not->toBeNull();
});

it('validates confirmed fields per question', function () {
    $this->actingAs($this->worker);

    $this->post(route('worker.voice.confirm', 'countries'), ['countries' => [['code' => 'XX', 'years' => 70]]])
        ->assertSessionHasErrors(['countries.0.code', 'countries.0.years']);
    $this->post(route('worker.voice.confirm', 'destinations'), ['destinations' => ['BD']])
        ->assertSessionHasErrors('destinations.0');
    $this->post(route('worker.voice.confirm', 'occupation'), ['occupation' => 'welder', 'specialization' => 'industrial-electrician'])
        ->assertSessionHasErrors('specialization');
    $this->post(route('worker.voice.submit', 'skills'), ['audio' => UploadedFile::fake()->create('answer.exe', 10, 'application/x-msdownload')])
        ->assertSessionHasErrors('audio');

    expect(VoiceAnswer::query()->where('status', VoiceAnswerStatus::Confirmed)->count())->toBe(0);
});

it('keeps answers private to their worker', function () {
    $this->actingAs($this->worker)->post(route('worker.voice.submit', 'experience'), ['text' => 'Eight years.']);
    $answer = VoiceAnswer::query()->firstOrFail();

    $this->actingAs(makeWorker())
        ->post(route('worker.voice.confirm', 'experience'), ['answer_id' => $answer->id, 'years_experience' => 8])
        ->assertForbidden();

    $this->actingAs(User::factory()->create())->get(route('worker.voice.show'))->assertForbidden();

    expect($answer->refresh()->status)->toBe(VoiceAnswerStatus::Extracted);
});

it('prunes old recordings that were never confirmed', function () {
    $this->actingAs($this->worker)->post(route('worker.voice.submit', 'skills'), ['audio' => fakeRecording()]);
    $answer = VoiceAnswer::query()->firstOrFail();

    expect(app(PruneVoiceRecordings::class)->handle())->toBe(0);

    $this->travel(31)->days();

    expect(app(PruneVoiceRecordings::class)->handle())->toBe(1)
        ->and($answer->refresh()->getFirstMedia(VoiceAnswer::AUDIO_COLLECTION))->toBeNull()
        ->and($answer->transcript)->not->toBeNull();
});
