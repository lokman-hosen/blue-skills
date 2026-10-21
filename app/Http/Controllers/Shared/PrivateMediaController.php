<?php

namespace App\Http\Controllers\Shared;

use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Messaging\Models\Message;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
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
            // A worker's own file, a scan shared with an application the viewer can see, or a file
            // on a mobility case checklist.
            $owner instanceof Document => $owner->owner_user_id === $request->user()?->id
                || $this->sharedWithViewer($owner, $request->user())
                || $this->onViewersCase($owner, $request->user())
                || $this->underStaffReview($owner, $request->user()),
            // Registration evidence: reviewers, and members who may edit the organization.
            $owner instanceof VerificationRequest => Gate::allows('review', Organization::class)
                || ($owner->subject instanceof Organization && Gate::allows('update', $owner->subject))
                // Mandate letters: staff reviewers, the agency and the named employer.
                || ($owner->subject instanceof JobMandate && (Gate::allows('review', JobMandate::class)
                    || Gate::allows('viewAny', [JobMandate::class, $owner->subject->agency])
                    || Gate::allows('viewAny', [JobMandate::class, $owner->subject->employer]))),
            // Message attachments: the conversation's parties only.
            $owner instanceof Message => Gate::allows('view', $owner->conversation),
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

    /**
     * Case checklist files (D-079): the worker sees every file on their case; the managing
     * organization sees its own files and what the worker is sharing; the employer of an agency
     * job only items marked for it.
     */
    private function onViewersCase(Document $document, ?User $viewer): bool
    {
        if ($viewer === null) {
            return false;
        }

        return CaseDocument::query()
            ->where('document_id', $document->id)
            ->with('mobilityCase.application.workerProfile', 'mobilityCase.managingOrganization', 'mobilityCase.employer')
            ->get()
            ->contains(function (CaseDocument $item) use ($viewer): bool {
                $case = $item->mobilityCase;

                if (! Gate::forUser($viewer)->allows('view', $case)) {
                    return false;
                }

                if ($viewer->isWorker()) {
                    return true;
                }

                if ($item->fromWorker() && ! $item->isShared()) {
                    return false;
                }

                $manager = $case->managingOrganization;

                return ($manager !== null && Gate::forUser($viewer)->allows('viewAny', [MobilityCase::class, $manager]))
                    || $item->employer_can_view;
            });
    }

    /**
     * A Verification Officer or Superuser opens a worker's file while reviewing it; every view
     * is written to the audit log (D-088).
     */
    private function underStaffReview(Document $document, ?User $viewer): bool
    {
        if ($viewer === null || ! $viewer->isStaff()) {
            return false;
        }

        $item = $document->evidenceItems()->get()->first(fn (EvidenceItem $item) => Gate::forUser($viewer)->allows('viewFile', $item));

        if ($item === null) {
            return false;
        }

        activity('verification')->performedOn($item)->causedBy($viewer)
            ->withProperties(['document' => $document->id, 'type' => $document->type->value])
            ->log('document.viewed_by_staff');

        return true;
    }
}
