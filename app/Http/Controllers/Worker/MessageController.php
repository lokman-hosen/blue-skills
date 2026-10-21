<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Applications\Models\Application;
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
 * The worker's Messages tab: one conversation per application (spec §9, prototype "Messages").
 */
class MessageController extends Controller
{
    use ResolvesWorkerProfile;

    public function index(Request $request): Response
    {
        $profile = $this->workerProfile($request);
        $user = $request->user();

        $conversations = Conversation::query()
            ->where('worker_profile_id', $profile->id)
            ->with(['application.job', 'organization', 'workerProfile.user'])
            ->withCount(['messages as unread_count' => fn ($messages) => Unread::messages($messages, $user)])
            ->orderByRaw('last_message_at desc nulls last')
            ->get();

        return Inertia::render('worker/messages/index', [
            'conversations' => $conversations->map(fn (Conversation $conversation) => ConversationListItemData::fromConversation($conversation, $user))->values()->all(),
            'reportCategories' => ReportMessage::CATEGORIES,
        ]);
    }

    public function show(Request $request, Conversation $conversation, MarkConversationRead $markRead): Response
    {
        $this->workerProfile($request);
        Gate::authorize('view', $conversation);

        $markRead->handle($conversation, $request->user());

        return Inertia::render('worker/messages/show', [
            'conversation' => ConversationData::fromConversation($conversation, $request->user(), Gate::allows('post', $conversation)),
            'reportCategories' => ReportMessage::CATEGORIES,
            'maxFiles' => SendMessageRequest::MAX_FILES,
            'maxSizeMb' => SendMessageRequest::MAX_KB / 1024,
        ]);
    }

    public function store(SendMessageRequest $request, Conversation $conversation, SendMessage $send): RedirectResponse
    {
        Gate::authorize('post', $conversation);

        $send->handle($conversation, $request->user(), $request->string('body')->trim()->toString(), $request->attachments());

        return to_route('worker.messages.show', $conversation);
    }

    /**
     * "Message the employer" from an application.
     */
    public function open(Request $request, Application $application, OpenConversation $open): RedirectResponse
    {
        Gate::authorize('respond', $application);

        return to_route('worker.messages.show', $open->handle($application));
    }
}
