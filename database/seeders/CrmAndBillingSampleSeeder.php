<?php

namespace Database\Seeders;

use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Billing\Enums\CreditReason;
use App\Domain\Billing\Enums\PaymentMethod;
use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\OrganizationSubscription;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\States\Invoice\Issued;
use App\Domain\Billing\States\Invoice\Paid;
use App\Domain\Crm\Enums\CampaignChannel;
use App\Domain\Crm\Enums\CampaignLanding;
use App\Domain\Crm\Enums\ContactChannel;
use App\Domain\Crm\Enums\ContactOutcome;
use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\States\Campaign\Paused;
use App\Domain\Crm\Support\LeadProgress;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Module 9 sample data (D-103): a CRM agent and a finance officer, campaigns with daily visits,
 * a lead for every sample worker plus people met at the Dhaka job fair, recruitment project
 * RP-0042 on ABC Contracting, sample plans (prices not approved), subscriptions and invoices.
 * Runs after SampleWorldSeeder; all names are fictional.
 */
class CrmAndBillingSampleSeeder extends Seeder
{
    private User $agent;

    private User $finance;

    /** @var array<string, Campaign> */
    private array $campaigns = [];

    public function run(): void
    {
        $this->agent = $this->staff('M. Akter', 'm.akter@blueskills.test', StaffRole::CrmAgent);
        $this->finance = $this->staff('R. Nair', 'r.nair@blueskills.test', StaffRole::FinanceOfficer);

        $this->seedCampaigns();
        $this->seedLeads();
        $this->seedProject();
        $this->seedBilling();
    }

    private function seedCampaigns(): void
    {
        $meghna = $this->organization('Meghna Technical Training Centre');
        $definitions = [
            'fair' => ['Dhaka job fair 2026', 'DHAKA-JOB-FAIR-2026', CampaignChannel::JobFair, 'BD', null, CampaignLanding::Join, '2026-09-20', null, 60],
            'facebook' => ['Facebook Bangladesh electricians', 'FACEBOOK-BD-ELECTRIC', CampaignChannel::Social, 'BD', null, CampaignLanding::Jobs, '2026-09-01', null, 140],
            'meghna' => ['Meghna graduates 2026', 'MEGHNA-GRADUATES-2026', CampaignChannel::Institute, 'BD', $meghna->id, CampaignLanding::Join, '2026-09-10', null, 12],
            'radio' => ['Nepal FM radio spot', 'NEPAL-FM-RADIO-SPOT', CampaignChannel::RadioTv, 'NP', null, CampaignLanding::Join, '2026-08-15', '2026-12-31', 35],
        ];

        foreach ($definitions as $key => [$name, $code, $channel, $country, $partner, $landing, $starts, $ends, $dailyVisits]) {
            $campaign = $this->campaigns[$key] = Campaign::query()->create([
                'code' => $code, 'name' => $name, 'channel' => $channel, 'source_country_code' => $country,
                'partner_organization_id' => $partner, 'landing' => $landing, 'starts_on' => $starts, 'ends_on' => $ends,
                'created_by' => $this->agent->id,
            ]);

            // Steady, repeatable daily counts for the last 30 days of the campaign.
            for ($day = 29; $day >= 0; $day--) {
                $date = now()->subDays($day);
                if ($date->lt(Carbon::parse($starts))) {
                    continue;
                }
                $campaign->visits()->create(['day' => $date->toDateString(), 'visits' => $dailyVisits + (($day * 7) % 11) - 5]);
            }
        }

        $this->campaigns['radio']->update(['status' => Paused::class]);
    }

    private function seedLeads(): void
    {
        $meghna = $this->organization('Meghna Technical Training Centre');

        $daysAgo = 30;
        foreach ($this->workerLeads($meghna->id) as [$phone, $source, $campaign, $partner, $status, $next, $assigned]) {
            $user = User::query()->where('phone', $phone)->first();
            if ($user === null) {
                continue;
            }

            $stage = LeadProgress::stageFor($user);
            $lead = Lead::query()->create([
                'user_id' => $user->id, 'source' => $source, 'campaign_id' => $campaign !== null ? $this->campaigns[$campaign]->id : null,
                'partner_organization_id' => $partner ?? ($campaign !== null ? $this->campaigns[$campaign]->partner_organization_id : null),
                'country_code' => $user->workerProfile?->current_country_code,
                'assigned_to' => $assigned ? $this->agent->id : null,
                'follow_up_status' => $status, 'next_follow_up_at' => $next !== null ? now()->addDays($next)->setTime(10, 0) : null,
                'last_contacted_at' => $status === FollowUpStatus::New ? null : now()->subDays(4),
                'conversion_stage' => $stage, 'stage_reached_at' => now()->subDays(10),
            ]);
            $lead->forceFill(['created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays(2)])->saveQuietly();
            $daysAgo -= 3;

            if ($status !== FollowUpStatus::New) {
                $this->attempt($lead, match ($status) {
                    FollowUpStatus::CallBack => [ContactChannel::PhoneCall, ContactOutcome::CallBack, 'Driving now, call back in the evening.'],
                    FollowUpStatus::NoAnswer => [ContactChannel::PhoneCall, ContactOutcome::NoAnswer, null],
                    FollowUpStatus::NotInterested => [ContactChannel::WhatsApp, ContactOutcome::NotInterested, 'Staying in Bangladesh this year.'],
                    default => [ContactChannel::WhatsApp, ContactOutcome::Reached, 'Helped add the work reference to the Skill Passport.'],
                });
            }
        }

        // People met at the Dhaka job fair who haven't signed up yet.
        $fair = $this->campaigns['fair'];
        $prospects = [
            ['Abdul Karim', '+8801712000101', FollowUpStatus::New, 0],
            ['Jahid Hasan', '+8801712000102', FollowUpStatus::CallBack, 1],
            ['Mizanur Rahman', '+8801712000103', FollowUpStatus::DoNotContact, null],
        ];
        foreach ($prospects as [$name, $phone, $status, $next]) {
            $lead = Lead::query()->create([
                'name' => $name, 'phone' => $phone, 'source' => LeadSource::Campaign, 'campaign_id' => $fair->id, 'country_code' => 'BD',
                'assigned_to' => $this->agent->id, 'follow_up_status' => $status,
                'next_follow_up_at' => $next !== null ? now()->addDays($next)->setTime(15, 0) : null,
                'conversion_stage' => ConversionStage::Prospect, 'created_by' => $this->agent->id,
            ]);
            $lead->forceFill(['created_at' => now()->subDays(25)])->saveQuietly();
            if ($status === FollowUpStatus::CallBack) {
                $this->attempt($lead, [ContactChannel::InPerson, ContactOutcome::CallBack, 'Met at stall 14. Wants help recording the voice profile.']);
            }
        }
    }

    /**
     * Phone, source, campaign key, partner, follow-up, next follow-up (days from today), assigned.
     *
     * @return list<array{0: string, 1: LeadSource, 2: ?string, 3: ?string, 4: FollowUpStatus, 5: ?int, 6: bool}>
     */
    private function workerLeads(string $meghnaId): array
    {
        return [
            ['+8801711000001', LeadSource::Campaign, 'fair', null, FollowUpStatus::Contacted, null, true],
            ['+8801711000002', LeadSource::Institute, 'meghna', $meghnaId, FollowUpStatus::Contacted, 3, true],
            ['+923001000003', LeadSource::Direct, null, null, FollowUpStatus::New, null, false],
            ['+9779801000004', LeadSource::Campaign, 'radio', null, FollowUpStatus::CallBack, 0, true],
            ['+919801000005', LeadSource::Direct, null, null, FollowUpStatus::NoAnswer, -1, true],
            ['+94771000006', LeadSource::Direct, null, null, FollowUpStatus::New, null, false],
            ['+919801000007', LeadSource::Direct, null, null, FollowUpStatus::Contacted, null, false],
            ['+8801711000008', LeadSource::Campaign, 'facebook', null, FollowUpStatus::NotInterested, null, true],
            ['+9779801000009', LeadSource::Campaign, 'radio', null, FollowUpStatus::New, 1, false],
        ];
    }

    private function seedProject(): void
    {
        $abc = $this->organization('ABC Contracting');
        $project = RecruitmentProject::query()->create([
            'organization_id' => $abc->id, 'reference' => 'RP-0042', 'name' => '200 Electricians — Dubai', 'target_headcount' => 200,
            'destination_country_code' => 'AE', 'source_country_codes' => ['BD', 'NP', 'IN'], 'target_date' => '2027-03-31',
            'created_by' => User::query()->where('email', 'owner@abc-contracting.test')->value('id'),
        ]);
        JobPosting::query()->where('organization_id', $abc->id)->whereIn('public_code', ['AE-MT-0115', 'AE-ST-0042'])
            ->update(['recruitment_project_id' => $project->id]);
        $project->members()->sync(User::query()->whereIn('email', ['owner@abc-contracting.test', 'sara@abc-contracting.test'])->pluck('id')->all());
    }

    private function seedBilling(): void
    {
        $note = 'Sample prices for review; not approved for launch.';
        $plans = [
            'starter' => ['Employer Starter', OrganizationType::Employer, 75_000, BillingPeriod::Monthly, 5, 3, false, false, 1],
            'standard' => ['Employer Standard', OrganizationType::Employer, 150_000, BillingPeriod::Monthly, 15, 10, true, true, 2],
            'enterprise' => ['Employer Enterprise', OrganizationType::Employer, 1_200_000, BillingPeriod::Yearly, 250, null, true, true, 3],
            'agency' => ['Agency Professional', OrganizationType::Agency, 120_000, BillingPeriod::Monthly, 10, 10, true, false, 1],
        ];
        $models = [];
        foreach ($plans as $key => [$name, $type, $price, $period, $credits, $seats, $talent, $projects, $sort]) {
            $models[$key] = Plan::query()->create([
                'code' => $type->value.'-'.$key, 'name' => $name, 'description' => $note, 'organization_type' => $type,
                'price_amount_minor' => $price, 'price_currency' => 'AED', 'billing_period' => $period, 'posting_credits' => $credits,
                'seats' => $seats, 'talent_search' => $talent, 'recruitment_projects' => $projects, 'sort_order' => $sort,
            ]);
        }

        $abc = $this->organization('ABC Contracting');
        $northstar = $this->organization('Northstar Manpower');
        $abcSubscription = OrganizationSubscription::query()->create(['organization_id' => $abc->id, 'plan_id' => $models['standard']->id, 'starts_on' => '2026-09-01', 'created_by' => $this->finance->id]);
        $northstarSubscription = OrganizationSubscription::query()->create(['organization_id' => $northstar->id, 'plan_id' => $models['agency']->id, 'starts_on' => '2026-09-01', 'created_by' => $this->finance->id]);

        $september = $this->invoice($abcSubscription, '2026-09-01', Paid::class);
        $september->update(['paid_at' => '2026-09-08 11:00', 'payment_method' => PaymentMethod::BankTransfer, 'payment_reference' => 'FT26251-0042']);
        $this->credit(['organization_id' => $abc->id, 'delta' => 15, 'reason' => CreditReason::PlanGrant, 'note' => 'Employer Standard', 'invoice_id' => $september->id, 'actor_id' => $this->finance->id], '2026-09-08 11:00');
        $this->credit([
            'organization_id' => $abc->id, 'delta' => -1, 'reason' => CreditReason::JobPublished,
            'job_id' => JobPosting::query()->where('public_code', 'AE-MT-0115')->value('id'),
        ], '2026-09-20 09:30');
        $this->invoice($abcSubscription, '2026-10-15', Issued::class);

        $agencySeptember = $this->invoice($northstarSubscription, '2026-09-25', Issued::class);
        $agencySeptember->update(['due_on' => '2026-10-09']);
        $this->credit(['organization_id' => $northstar->id, 'delta' => 5, 'reason' => CreditReason::Adjustment, 'note' => 'Pilot partner goodwill credits', 'actor_id' => $this->finance->id], '2026-09-26 10:00');

        // A draft finance is still preparing (organizations never see drafts).
        $draft = $northstar->invoices()->create(['currency' => 'AED', 'tax_rate_bp' => 500, 'created_by' => $this->finance->id]);
        $draft->lines()->create(['description' => 'Extra posting credits', 'quantity' => 5, 'unit_amount_minor' => 10_000, 'amount_minor' => 50_000, 'credits' => 1]);
        $draft->recalculate();
    }

    /**
     * @param  class-string  $status
     */
    private function invoice(OrganizationSubscription $subscription, string $issuedOn, string $status): Invoice
    {
        $plan = $subscription->plan;
        $issued = Carbon::parse($issuedOn)->setTime(9, 0);

        $invoice = $subscription->organization->invoices()->create([
            'organization_subscription_id' => $subscription->id, 'status' => $status, 'number' => Invoice::newNumber(),
            'currency' => $plan->price_currency, 'tax_rate_bp' => 500, 'issued_at' => $issued,
            'due_on' => $issued->copy()->addDays(14)->toDateString(), 'created_by' => $this->finance->id,
        ]);
        $invoice->lines()->create([
            'description' => $plan->name.' · '.$issued->format('F Y'), 'quantity' => 1, 'unit_amount_minor' => $plan->price_amount_minor,
            'amount_minor' => $plan->price_amount_minor, 'plan_id' => $plan->id, 'credits' => $plan->posting_credits,
        ]);
        $invoice->recalculate();

        return $invoice;
    }

    /**
     * Ledger entries are append-only, so the sample date is set before the insert.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function credit(array $attributes, string $at): void
    {
        (new CreditLedgerEntry)->forceFill([...$attributes, 'created_at' => Carbon::parse($at)])->save();
    }

    /**
     * @param  array{0: ContactChannel, 1: ContactOutcome, 2: ?string}  $attempt
     */
    private function attempt(Lead $lead, array $attempt): void
    {
        $lead->contactAttempts()->create([
            'staff_id' => $this->agent->id, 'channel' => $attempt[0], 'outcome' => $attempt[1], 'note' => $attempt[2],
            'attempted_at' => $lead->last_contacted_at ?? now()->subDays(4),
        ]);
    }

    private function staff(string $name, string $email, StaffRole $role): User
    {
        $user = User::query()->create([
            'type' => UserType::Staff, 'name' => $name, 'email' => $email, 'password' => SampleWorldSeeder::PASSWORD,
            'timezone' => 'Asia/Dubai', 'data_region' => config('blueskills.default_data_region'),
        ]);
        $user->forceFill(['email_verified_at' => now()->subMonths(2)])->save();

        return $user->assignStaffRole($role);
    }

    private function organization(string $displayName): Organization
    {
        return Organization::query()->where('display_name', $displayName)->firstOrFail();
    }
}
