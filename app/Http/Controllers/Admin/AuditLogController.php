<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Trust\Data\AuditEntryData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * The audit log (spec §10): every recorded change, filtered by area, person, record and date.
 * Superusers only, since entries can name people and documents.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAuditLog');

        $filters = [
            'area' => $request->string('area')->toString() ?: null,
            'actor' => mb_substr($request->string('actor')->trim()->toString(), 0, 100) ?: null,
            'subject' => mb_substr($request->string('subject')->trim()->toString(), 0, 40) ?: null,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
        ];

        $entries = Activity::query()
            ->when($filters['area'], fn ($query, string $area) => $query->where('log_name', $area))
            ->when($filters['actor'], fn ($query, string $actor) => $query->whereHasMorph('causer', '*', fn ($users) => $users
                ->where('name', 'ilike', "%{$actor}%")->orWhere('email', 'ilike', "%{$actor}%")))
            ->when($filters['subject'], fn ($query, string $subject) => $query->where('subject_id', $subject))
            ->when($filters['from'], fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->with('causer')
            ->latest()
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/audit', [
            'entries' => $entries->through(fn (Activity $activity) => AuditEntryData::fromActivity($activity)),
            'filters' => $filters,
            'areas' => Activity::query()->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name')->all(),
        ]);
    }
}
