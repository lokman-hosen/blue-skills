<?php

namespace App\Http\Controllers\Shared;

use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\VoiceAnswer;
use App\Domain\Worker\Models\WorkerProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves private media behind a short-lived signed link AND the owning model's policy
 * (CLAUDE.md: sensitive files). Local disks stream with range support so video can seek.
 */
class PrivateMediaController extends Controller
{
    public function __invoke(Request $request, Media $media): BinaryFileResponse|RedirectResponse
    {
        $owner = $media->model;

        $allowed = match (true) {
            $owner instanceof WorkerProfile, $owner instanceof SkillShow, $owner instanceof VoiceAnswer => Gate::allows('view', $owner),
            // A worker's own file, or a scan shared with an application the viewer can see.
            $owner instanceof Document => $owner->owner_user_id === $request->user()?->id || $this->sharedWithViewer($owner, $request->user()),
            // Registration evidence: reviewers, and members who may edit the organization.
            $owner instanceof VerificationRequest => Gate::allows('review', Organization::class)
                || ($owner->subject instanceof Organization && Gate::allows('update', $owner->subject)),
            default => false,
        };

        abort_unless($allowed, 403);

        $disk = Storage::disk($media->disk);
        $headers = [
            'Content-Type' => $media->mime_type ?? 'application/octet-stream',
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (config("filesystems.disks.{$media->disk}.driver") === 'local') {
            return response()->file($disk->path($media->getPathRelativeToRoot()), $headers);
        }

        return redirect()->away($disk->temporaryUrl($media->getPathRelativeToRoot(), now()->addMinutes(5)));
    }

    /**
     * Product rule 6: per application, per item, only while the consent is active.
     */
    private function sharedWithViewer(Document $document, ?User $viewer): bool
    {
        if ($viewer === null || $viewer->isWorker()) {
            return false;
        }

        return ApplicationConsent::query()->active()
            ->where('document_id', $document->id)
            ->with('application.job.organization')
            ->get()
            ->contains(fn (ApplicationConsent $consent) => Gate::forUser($viewer)->allows('view', $consent->application));
    }
}
