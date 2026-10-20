<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Config\Data\LocaleOptionData;
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\Language;
use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Actions\AddWorkerCredential;
use App\Domain\Worker\Actions\DeleteWorkerExperience;
use App\Domain\Worker\Actions\RemoveWorkerCredential;
use App\Domain\Worker\Actions\SaveWorkerExperience;
use App\Domain\Worker\Actions\SyncWorkerLanguages;
use App\Domain\Worker\Actions\SyncWorkerSkills;
use App\Domain\Worker\Actions\UpdatePassportAbout;
use App\Domain\Worker\Actions\UpdateWorkerPhoto;
use App\Domain\Worker\Actions\UpdateWorkerPreferences;
use App\Domain\Worker\Data\ProfileOptionsData;
use App\Domain\Worker\Data\WorkerPassportData;
use App\Domain\Worker\Models\WorkerExperience;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\AddWorkerCredentialRequest;
use App\Http\Requests\Worker\SaveWorkerExperienceRequest;
use App\Http\Requests\Worker\SyncWorkerLanguagesRequest;
use App\Http\Requests\Worker\SyncWorkerSkillsRequest;
use App\Http\Requests\Worker\UpdatePassportAboutRequest;
use App\Http\Requests\Worker\UpdateWorkerPhotoRequest;
use App\Http\Requests\Worker\UpdateWorkerPreferencesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The worker's Global Skill Passport and its sections, all edited by the worker only.
 */
class PassportController extends Controller
{
    use ResolvesWorkerProfile;

    public const SECTIONS = ['about', 'experience', 'skills', 'languages', 'preferences', 'photo', 'evidence'];

    public function show(Request $request): Response
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('view', $profile);

        return Inertia::render('worker/passport/show', [
            'passport' => WorkerPassportData::fromProfile($profile),
        ]);
    }

    public function edit(Request $request, string $section): Response
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        return Inertia::render('worker/passport/edit', [
            'section' => $section,
            'passport' => WorkerPassportData::fromProfile($profile),
            'options' => ProfileOptionsData::load(),
            'languages' => Language::query()->orderBy('english_name')->get()
                ->map(fn (Language $language) => LocaleOptionData::fromLanguage($language))
                ->values()
                ->all(),
            'currencies' => Country::query()->distinct()->orderBy('currency')->pluck('currency')->values()->all(),
            'photoMaxMb' => UpdateWorkerPhotoRequest::MAX_KB / 1024,
            'evidenceTypes' => AddWorkerCredential::TYPES,
            'evidenceMaxMb' => AddWorkerCredentialRequest::MAX_KB / 1024,
        ]);
    }

    public function storeCredential(AddWorkerCredentialRequest $request, AddWorkerCredential $add): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $document = $request->file('document');
        abort_unless($document instanceof UploadedFile, 422);

        $add->handle(
            $profile,
            EvidenceType::from($request->string('type')->toString()),
            $request->string('title')->trim()->toString(),
            $request->filled('expires_on') ? $request->string('expires_on')->toString() : null,
            $document,
        );

        return $this->saved('evidence');
    }

    public function destroyCredential(Request $request, EvidenceItem $evidence, RemoveWorkerCredential $remove): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);
        abort_unless($evidence->worker_profile_id === $profile->id, 404);

        $remove->handle($evidence);

        return $this->saved('evidence');
    }

    public function updateAbout(UpdatePassportAboutRequest $request, UpdatePassportAbout $update): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $update->handle($profile, $request->fields());

        return $this->saved();
    }

    public function storeExperience(SaveWorkerExperienceRequest $request, SaveWorkerExperience $save): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $save->handle($profile, null, $request->fields());

        return $this->saved('experience');
    }

    public function updateExperience(SaveWorkerExperienceRequest $request, WorkerExperience $experience, SaveWorkerExperience $save): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);
        abort_unless($experience->worker_profile_id === $profile->id, 404);

        $save->handle($profile, $experience, $request->fields());

        return $this->saved('experience');
    }

    public function destroyExperience(Request $request, WorkerExperience $experience, DeleteWorkerExperience $delete): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);
        abort_unless($experience->worker_profile_id === $profile->id, 404);

        $delete->handle($experience);

        return $this->saved('experience');
    }

    public function updateSkills(SyncWorkerSkillsRequest $request, SyncWorkerSkills $sync): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $sync->handle($profile, $request->skillIds());

        return $this->saved();
    }

    public function updateLanguages(SyncWorkerLanguagesRequest $request, SyncWorkerLanguages $sync): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $sync->handle($profile, $request->languages());

        return $this->saved();
    }

    public function updatePreferences(UpdateWorkerPreferencesRequest $request, UpdateWorkerPreferences $update): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $update->handle($profile, $request->fields());

        return $this->saved();
    }

    public function updatePhoto(UpdateWorkerPhotoRequest $request, UpdateWorkerPhoto $update): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $photo = $request->file('photo');
        abort_unless($photo instanceof UploadedFile, 422);
        $update->handle($profile, $photo);

        return $this->saved();
    }

    public function destroyPhoto(Request $request, UpdateWorkerPhoto $update): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $update->handle($profile, null);

        return $this->saved();
    }

    /**
     * Back to the Passport, or stay on the section when the worker edits a list.
     */
    private function saved(?string $stayOn = null): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => __('worker.passport.saved')]);

        return $stayOn !== null
            ? to_route('worker.passport.edit', ['section' => $stayOn])
            : to_route('worker.passport.show');
    }
}
