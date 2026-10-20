<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Organization\Actions\DecideOrganizationReview;
use App\Domain\Organization\Data\EvidenceDocumentData;
use App\Domain\Organization\Data\OrganizationReviewData;
use App\Domain\Organization\Data\TeamMemberData;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationMember;
use App\Domain\Organization\Support\OrganizationRoles;
use App\Domain\Verification\Data\ReviewDecisionData;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\VerificationRequest\InReview;
use App\Domain\Verification\States\VerificationRequest\Open;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewDecisionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Staff review of employer, agency and institute registrations (spec §1, §10).
 */
class OrganizationReviewController extends Controller
{
    private const TYPES = [VerificationType::Organization, VerificationType::AgencyLicence, VerificationType::InstituteAccreditation];

    public function index(): Response
    {
        Gate::authorize('review', Organization::class);

        $reviews = VerificationRequest::query()
            ->with(['subject', 'media'])
            ->whereIn('type', self::TYPES)
            ->whereIn('status', ['open', 'in_review', 'needs_info'])
            ->oldest()
            ->limit(100)
            ->get();

        return Inertia::render('admin/organizations/index', [
            'reviews' => $reviews->map(fn (VerificationRequest $review) => OrganizationReviewData::fromRequest($review))->values()->all(),
        ]);
    }

    public function show(VerificationRequest $review, OrganizationRoles $roles): Response
    {
        Gate::authorize('review', Organization::class);
        abort_unless(in_array($review->type, self::TYPES, true), 404);

        $organization = $review->subject;
        abort_unless($organization instanceof Organization, 404);

        return Inertia::render('admin/organizations/show', [
            'review' => OrganizationReviewData::fromRequest($review),
            'documents' => $review->getMedia(VerificationRequest::EVIDENCE_COLLECTION)
                ->map(fn (Media $media) => EvidenceDocumentData::fromMedia($media, withUrl: true))
                ->values()
                ->all(),
            'members' => $organization->members()->with('user')->orderBy('joined_at')->get()
                ->map(fn (OrganizationMember $member) => new TeamMemberData(
                    id: $member->id,
                    name: $member->user->name,
                    email: $member->user->email,
                    title: $member->title,
                    role: $roles->roleOf($member->user, $organization),
                    joinedAt: $member->joined_at?->toIso8601String(),
                    isYou: false,
                ))
                ->values()
                ->all(),
            'history' => $organization->verificationRequests()->with('decider')->whereNotNull('decision')->latest('decided_at')->get()
                ->map(fn (VerificationRequest $past) => ReviewDecisionData::fromRequest($past, withReviewer: true))
                ->filter()
                ->values()
                ->all(),
            'decidable' => $review->status->equals(Open::class, InReview::class),
            'reasons' => ReviewReason::forOrganizations(),
        ]);
    }

    public function decide(ReviewDecisionRequest $request, VerificationRequest $review, DecideOrganizationReview $decide): RedirectResponse
    {
        Gate::authorize('review', Organization::class);
        abort_unless(in_array($review->type, self::TYPES, true), 404);

        $decide->handle($review, $request->user(), $request->decision(), $request->reason(), $request->note());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("admin.review.done.{$request->decision()->value}")]);

        return to_route('admin.organizations.index');
    }
}
