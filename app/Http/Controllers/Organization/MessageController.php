<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Applications\Models\Application;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Messaging\Actions\MarkConversationRead;
use App\Domain\Messaging\Actions\OpenConversation;
use App\Domain\Messaging\Actions\ReportMessage;
use App\Domain\Messaging\Actions\SendMessage;
use App\Domain\Messaging\Data\ConversationData;
use App\Domain\Messaging\Data\ConversationListItemData;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Support\Unread;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\SendMessageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The organization's inbox: conversations with applicants, filterable by job (spec §9).
 */
class MessageController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewAny', [Conversation::class, $organization]);
        $user = $request->user();
        $job = $request->filled('job') ? $organization->postedJobs()->where('public_code', $request->string('job')->toString())->first() : null;

        $conversations = Conversation::query()
            ->where('organization_id', $organization->id)
            ->when($job !== null, fn ($query) => $query->whereHas('application', fn ($application) => $application->where('job_id', $job?->id)))
            ->with(['application.job', 'organization', 'workerProfile.user'])
            ->withCount(['messages as unread_count' => fn ($messages) => Unread::messages($messages, $user)])
            ->orderByRaw('last_message_at desc nulls last')
            ->limit(200)
            ->get();

        return Inertia::render('org/messages/index', [
            'conversations' => $conversations->map(fn (Conversation $conversation) => ConversationListItemData::fromConversation($conversation, $user))->values()->all(),
            'job' => $job?->public_code,
            'jobs' => $organization->postedJobs()->whereHas('applications')->latest('updated_at')->get(['public_code', 'title'])
                ->map(fn (JobPosting $posting) => ['code' => $posting->public_code, 'title' => $posting->title])->values()->all(),
        ]);
    }

    public function show(Request $request, Conversation $conversation, MarkConversationRead $markRead): Response
    {
        $this->ownConversation($request, $conversation);
        Gate::authorize('view', $conversation);

        $markRead->handle($conversation, $request->user());

        return Inertia::render('org/messages/show', [
            'conversation' => ConversationData::fromConversation($conversation, $request->user(), Gate::allows('post', $conversation)),
            'reportCategories' => ReportMessage::CATEGORIES,
            'maxFiles' => SendMessageRequest::MAX_FILES,
            'maxSizeMb' => SendMessageRequest::MAX_KB / 1024,
        ]);
    }

    public function store(SendMessageRequest $request, Conversation $conversation, SendMessage $send): RedirectResponse
    {
        $this->ownConversation($request, $conversation);
        Gate::authorize('post', $conversation);

        $send->handle($conversation, $request->user(), $request->string('body')->trim()->toString(), $request->attachments());

        return to_route('org.messages.show', $conversation);
    }

    /**
     * "Message the worker" from the applicant page.
     */
    public function open(Request $request, Application $application, OpenConversation $open): RedirectResponse
    {
        $organization = $this->organization($request);
        abort_unless($application->job->organization_id === $organization->id, 404);
        Gate::authorize('view', $application);

        return to_route('org.messages.show', $open->handle($application));
    }

    private function ownConversation(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->organization_id === $this->organization($request)->id, 404);
    }
}
