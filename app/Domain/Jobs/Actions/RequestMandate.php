<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Organization\Actions\AttachVerificationEvidence;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\States\VerificationRequest\Open;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An agency asks an employer on BLUESKILLS for a recruitment mandate (D-074): trades, destination,
 * headcount and dates, with the signed mandate letter. The employer confirms in the app, then
 * staff check the letter and the licence (product rule 10).
 */
class RequestMandate
{
    public function __construct(private readonly AttachVerificationEvidence $attach) {}

    /**
     * @param  array{occupation_ids: list<string>, destination_country_code: string, headcount: int, valid_from: ?string, valid_until: string}  $scope
     */
    public function handle(Organization $agency, Organization $employer, array $scope, UploadedFile $letter, User $actor): JobMandate
    {
        if (! $agency->hasValidLicence($scope['destination_country_code'])) {
            throw ValidationException::withMessages(['destination_country_code' => __('mandates.licence_not_valid')]);
        }

        if ($employer->type !== OrganizationType::Employer || ! $employer->isVerified()) {
            throw ValidationException::withMessages(['employer' => __('mandates.employer_not_verified')]);
        }

        $mandate = DB::transaction(function () use ($agency, $employer, $scope, $letter, $actor): JobMandate {
            /** @var JobMandate $mandate */
            $mandate = JobMandate::query()->create([
                ...$scope,
                'agency_organization_id' => $agency->id,
                'employer_organization_id' => $employer->id,
                'reference' => self::newReference($agency),
                'status' => MandateStatus::AwaitingEmployer,
                'requested_by' => $actor->id,
            ]);

            $review = $mandate->verificationRequests()->create(['type' => VerificationType::JobMandate, 'status' => Open::class]);
            $this->attach->handle($review, $letter, $actor);

            activity('mandates')->performedOn($mandate)->causedBy($actor)
                ->withProperties(['employer' => $employer->id, 'scope' => $scope])
                ->log('mandate.requested');

            return $mandate;
        });

        foreach (Notify::members($employer, Permission::MandatesConfirm) as $member) {
            Notify::user($member, NotificationEvent::MandateRequested, ['agency' => $agency->display_name, 'reference' => $mandate->reference], route('org.mandates.show', $mandate, false));
        }

        return $mandate;
    }

    /**
     * MD-2026-4821: year and four random digits, unique per agency.
     */
    private static function newReference(Organization $agency): string
    {
        do {
            $reference = 'MD-'.now()->year.'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (JobMandate::query()->where('agency_organization_id', $agency->id)->where('reference', $reference)->exists());

        return $reference;
    }
}
