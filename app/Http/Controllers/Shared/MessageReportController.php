<?php

namespace App\Http\Controllers\Shared;

use App\Domain\Messaging\Actions\ReportMessage;
use App\Domain\Messaging\Models\Message;
use App\Domain\Trust\Enums\ReportCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\ReportMessageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * "Report this message" for either side of a conversation.
 */
class MessageReportController extends Controller
{
    public function __invoke(ReportMessageRequest $request, Message $message, ReportMessage $report): RedirectResponse
    {
        Gate::authorize('view', $message->conversation);

        $report->handle(
            $message,
            $request->user(),
            ReportCategory::from($request->string('category')->toString()),
            $request->filled('description') ? $request->string('description')->trim()->toString() : null,
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.reported')]);

        return back();
    }
}
