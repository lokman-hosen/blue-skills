<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Institute\Actions\AttestTraining;
use App\Domain\Institute\Actions\RecordTradeAssessment;
use App\Domain\Institute\Actions\WithdrawInstituteRecord;
use App\Domain\Institute\Enums\AssessmentResult;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\AttestTrainingRequest;
use App\Http\Requests\Organization\RecordAssessmentRequest;
use App\Http\Requests\Organization\WithdrawRecordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Attestations and assessments the institute issues to graduates who accepted (spec §7,
 * product rules 9 and 11), and withdrawing them (D-082).
 */
class GraduateRecordController extends Controller
{
    use ResolvesCurrentOrganization;

    public function attest(AttestTrainingRequest $request, Cohort $cohort, CohortMember $member, AttestTraining $attest): RedirectResponse
    {
        $this->ownCohort($request, $cohort);
        Gate::authorize('attest', $member);

        $attest->handle($member, $request->string('completed_on')->toString(), $request->string('note')->trim()->toString() ?: null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('institute.records.attested')]);

        return to_route('org.cohorts.show', $cohort);
    }

    public function assess(RecordAssessmentRequest $request, Cohort $cohort, CohortMember $member, RecordTradeAssessment $record): RedirectResponse
    {
        $this->ownCohort($request, $cohort);
        Gate::authorize('assess', $member);

        $record->handle($member, [
            'title' => $request->string('title')->trim()->toString(),
            'result' => $request->enum('result', AssessmentResult::class) ?? AssessmentResult::Fail,
            'score' => $request->filled('score') ? $request->integer('score') : null,
            'assessed_on' => $request->string('assessed_on')->toString(),
            'note' => $request->string('note')->trim()->toString() ?: null,
        ], $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('institute.records.assessed')]);

        return to_route('org.cohorts.show', $cohort);
    }

    public function withdrawAttestation(WithdrawRecordRequest $request, TrainingAttestation $attestation, WithdrawInstituteRecord $withdraw): RedirectResponse
    {
        return $this->withdraw($request, $attestation, 'attest', $withdraw);
    }

    public function withdrawAssessment(WithdrawRecordRequest $request, TradeAssessment $assessment, WithdrawInstituteRecord $withdraw): RedirectResponse
    {
        return $this->withdraw($request, $assessment, 'assess', $withdraw);
    }

    private function withdraw(WithdrawRecordRequest $request, TrainingAttestation|TradeAssessment $record, string $ability, WithdrawInstituteRecord $withdraw): RedirectResponse
    {
        abort_unless($record->organization_id === $this->organization($request)->id, 404);
        Gate::authorize($ability, $record->cohortMember);

        $withdraw->handle($record, $request->string('reason')->trim()->toString(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('institute.records.withdrawn')]);

        return to_route('org.cohorts.show', $record->cohortMember->cohort_id);
    }

    private function ownCohort(Request $request, Cohort $cohort): void
    {
        abort_unless($cohort->organization_id === $this->organization($request)->id, 404);
    }
}
