<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Crm\Actions\CreateLead;
use App\Domain\Crm\Actions\LogContactAttempt;
use App\Domain\Crm\Actions\RecordLeadPhoneView;
use App\Domain\Crm\Actions\UpdateLeadFollowUp;
use App\Domain\Crm\Data\LeadData;
use App\Domain\Crm\Data\LeadListItemData;
use App\Domain\Crm\Enums\ContactChannel;
use App\Domain\Crm\Enums\ContactOutcome;
use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\Models\Lead;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LogContactAttemptRequest;
use App\Http\Requests\Admin\StoreLeadRequest;
use App\Http\Requests\Admin\UpdateLeadRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * The worker CRM (spec §9): leads with source, campaign, assignee, contact attempts, follow-up
 * status and conversion stage. CRM Agents and Superusers only.
 */
class LeadController extends Controller
{
    public const VIEWS = ['all', 'due', 'mine', 'unassigned'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Lead::class);

        $view = in_array($request->string('view')->toString(), self::VIEWS, true) ? $request->string('view')->toString() : 'all';
        $filters = [
            'status' => FollowUpStatus::tryFrom($request->string('status')->toString())?->value,
            'stage' => ConversionStage::tryFrom($request->string('stage')->toString())?->value,
            'source' => LeadSource::tryFrom($request->string('source')->toString())?->value,
            'campaign' => $request->string('campaign')->toString() ?: null,
            'q' => mb_substr($request->string('q')->trim()->toString(), 0, 80) ?: null,
        ];

        $leads = Lead::query()
            ->when($view === 'due', fn (Builder $query) => $query->where('next_follow_up_at', '<=', now()->endOfDay()))
            ->when($view === 'mine', fn (Builder $query) => $query->where('assigned_to', $request->user()?->id))
            ->when($view === 'unassigned', fn (Builder $query) => $query->whereNull('assigned_to')
                ->whereNotIn('follow_up_status', [FollowUpStatus::DoNotContact->value, FollowUpStatus::NotInterested->value]))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('follow_up_status', $status))
            ->when($filters['stage'], fn (Builder $query, string $stage) => $query->where('conversion_stage', $stage))
            ->when($filters['source'], fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['campaign'], fn (Builder $query, string $campaign) => $query->where('campaign_id', $campaign))
            ->when($filters['q'], fn (Builder $query, string $q) => $query->where(fn (Builder $match) => $match
                ->where('name', 'ilike', "%{$q}%")
                ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'ilike', "%{$q}%"))))
            ->with(['user', 'campaign', 'assignee'])
            ->orderByRaw('next_follow_up_at asc nulls last')
            ->latest()
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/crm/index', [
            'leads' => $leads->through(fn (Lead $lead) => LeadListItemData::fromLead($lead)),
            'view' => $view,
            'filters' => $filters,
            'campaigns' => $this->campaignOptions(),
            'countries' => config('blueskills.worker_phone_countries'),
        ]);
    }

    public function store(StoreLeadRequest $request, CreateLead $create): RedirectResponse
    {
        Gate::authorize('create', Lead::class);

        $phone = (new PhoneNumber($request->string('phone')->toString(), $request->string('country')->toString()))->formatE164();
        $existing = Lead::query()->where('phone', $phone)->orWhereHas('user', fn (Builder $user) => $user->where('phone', $phone))->first();

        if ($existing !== null) {
            throw ValidationException::withMessages(['phone' => __('crm.errors.duplicate', ['name' => $existing->displayName()])]);
        }

        $lead = $create->handle(
            $request->string('name')->trim()->toString(),
            $phone,
            $request->string('country')->toString(),
            LeadSource::from($request->string('source')->toString()),
            $request->string('campaign_id')->toString() ?: null,
            $request->boolean('assign_to_me') ? $request->user()?->id : null,
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('crm.done.created')]);

        return to_route('admin.crm.leads.show', $lead);
    }

    public function show(Request $request, Lead $lead, RecordLeadPhoneView $recordView): Response
    {
        Gate::authorize('view', $lead);
        $recordView->handle($lead, $request->user());

        return Inertia::render('admin/crm/lead', [
            'lead' => LeadData::fromLead($lead),
            'agents' => User::query()->where('type', UserType::Staff)->orderBy('name')->get()
                ->filter(fn (User $user) => $user->hasStaffPermission(Permission::CrmManage->value))
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])->values()->all(),
            'statuses' => FollowUpStatus::cases(),
            'channels' => ContactChannel::cases(),
            'outcomes' => ContactOutcome::cases(),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead, UpdateLeadFollowUp $update): RedirectResponse
    {
        Gate::authorize('update', $lead);

        $update->handle(
            $lead,
            $request->string('assigned_to')->toString() ?: null,
            FollowUpStatus::from($request->string('follow_up_status')->toString()),
            $request->date('next_follow_up_at'),
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('crm.done.updated')]);

        return to_route('admin.crm.leads.show', $lead);
    }

    public function attempt(LogContactAttemptRequest $request, Lead $lead, LogContactAttempt $log): RedirectResponse
    {
        Gate::authorize('update', $lead);

        $log->handle(
            $lead,
            ContactChannel::from($request->string('channel')->toString()),
            ContactOutcome::from($request->string('outcome')->toString()),
            $request->string('note')->trim()->toString() ?: null,
            $request->date('next_follow_up_at'),
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('crm.done.attempt')]);

        return to_route('admin.crm.leads.show', $lead);
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function campaignOptions(): array
    {
        return Campaign::query()->orderByDesc('starts_on')->get(['id', 'name'])
            ->map(fn (Campaign $campaign) => ['id' => $campaign->id, 'name' => $campaign->name])->values()->all();
    }
}
