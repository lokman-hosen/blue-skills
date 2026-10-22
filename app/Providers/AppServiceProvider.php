<?php

namespace App\Providers;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Applications\Models\ApplicationStageEvent;
use App\Domain\Applications\Models\Consent;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Applications\Models\Offer;
use App\Domain\Applications\Models\ProfileSnapshot;
use App\Domain\Applications\Models\Shortlist;
use App\Domain\Applications\Models\ShortlistEntry;
use App\Domain\Billing\Contracts\PaymentProvider;
use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\OrganizationSubscription;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Providers\ManualPaymentProvider;
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\CountryRule;
use App\Domain\Config\Models\Language;
use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadContactAttempt;
use App\Domain\Crm\Support\LeadStageWatcher;
use App\Domain\Identity\Channels\LogOtpChannel;
use App\Domain\Identity\Channels\WhatsAppCloudOtpChannel;
use App\Domain\Identity\Contracts\OtpChannel;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\StaffInvitation;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\Course;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Institute\Models\TalentPoolMember;
use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\JobSkillRequirement;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Jobs\Models\RecruitmentProjectMember;
use App\Domain\Messaging\Channels\CloudWhatsAppSender;
use App\Domain\Messaging\Channels\LogSmsSender;
use App\Domain\Messaging\Channels\LogWhatsAppSender;
use App\Domain\Messaging\Channels\TwilioSmsSender;
use App\Domain\Messaging\Contracts\SmsSender;
use App\Domain\Messaging\Contracts\WhatsAppSender;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Models\OutboundMessage;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseEvent;
use App\Domain\Mobility\Models\MobilityCaseStage;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationInvitation;
use App\Domain\Organization\Models\OrganizationMember;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Domain\Trust\Models\Appeal;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Models\TrustAction;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\Models\TrustCaseNote;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Worker\Contracts\ProfileExtractor;
use App\Domain\Worker\Contracts\SpeechToText;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\VoiceAnswer;
use App\Domain\Worker\Models\WorkerDestinationPreference;
use App\Domain\Worker\Models\WorkerExperience;
use App\Domain\Worker\Models\WorkerLanguage;
use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Models\WorkerSkill;
use App\Domain\Worker\Voice\RuleBasedProfileExtractor;
use App\Domain\Worker\Voice\SampleSpeechToText;
use App\Domain\Worker\Voice\UnavailableSpeechToText;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Platform billing payments (D-095): manual bank transfer until a provider is chosen.
        $this->app->bind(PaymentProvider::class, fn () => match (config('blueskills.billing.provider')) {
            default => new ManualPaymentProvider,
        });

        $this->app->bind(OtpChannel::class, fn () => match (config('blueskills.otp.channel')) {
            'whatsapp' => new WhatsAppCloudOtpChannel,
            default => new LogOtpChannel,
        });

        $this->app->bind(SpeechToText::class, fn () => match (config('blueskills.voice.speech_to_text')) {
            'sample' => new SampleSpeechToText,
            default => new UnavailableSpeechToText,
        });

        $this->app->bind(ProfileExtractor::class, RuleBasedProfileExtractor::class);

        $this->app->bind(WhatsAppSender::class, fn () => match (config('blueskills.notifications.whatsapp_driver')) {
            'cloud' => new CloudWhatsAppSender,
            default => new LogWhatsAppSender,
        });

        $this->app->bind(SmsSender::class, fn () => match (config('blueskills.notifications.sms_driver')) {
            'twilio' => new TwilioSmsSender,
            default => new LogSmsSender,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMorphMap();
        LeadStageWatcher::register();
        $this->configureGates();
        $this->configureRateLimiting();
    }

    /**
     * Rate limits (D-105). `writes` runs on every web route on top of any route's own limit;
     * reads are never limited by it. `worker-join` limits phone sign-in per device rather than
     * per IP address, so a job fair on one Wi-Fi network isn't locked out after ten workers.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('writes', function (Request $request): Limit {
            if ($request->isMethodSafe() || $request->is('broadcasting/auth')) {
                return Limit::none();
            }

            $user = $request->user();

            return $user instanceof User
                ? Limit::perMinute(config()->integer('blueskills.security.writes_per_minute'))->by('writes:user:'.$user->id)
                : Limit::perMinute(config()->integer('blueskills.security.guest_writes_per_minute'))->by('writes:ip:'.$request->ip());
        });

        RateLimiter::for('worker-join', fn (Request $request): array => [
            Limit::perMinute(10)->by('join:session:'.$request->session()->getId()),
            Limit::perMinute(300)->by('join:ip:'.$request->ip()),
        ]);
    }

    protected function configureGates(): void
    {
        Gate::define('viewDesignSystem', fn (User $user): bool => $user->isStaff());
        Gate::define('manageStaff', fn (User $user): bool => $user->hasStaffPermission(Permission::StaffManage->value));
        // Corridor rules, countries and languages; the occupation taxonomy (D-093).
        Gate::define('manageConfig', fn (User $user): bool => $user->hasStaffPermission(Permission::ConfigManage->value));
        Gate::define('viewAuditLog', fn (User $user): bool => $user->hasStaffPermission(Permission::StaffManage->value));
        Gate::define('manageTaxonomy', fn (User $user): bool => $user->hasStaffPermission(Permission::TaxonomyManage->value));
    }

    /**
     * Short, stable morph names so polymorphic columns never store PHP class names.
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'staff_invitation' => StaffInvitation::class,
            'organization' => Organization::class,
            'organization_member' => OrganizationMember::class,
            'organization_invitation' => OrganizationInvitation::class,
            'country' => Country::class,
            'country_rule' => CountryRule::class,
            'language' => Language::class,
            'sector' => Sector::class,
            'occupation' => Occupation::class,
            'specialization' => Specialization::class,
            'skill' => Skill::class,
            'worker_profile' => WorkerProfile::class,
            'worker_experience' => WorkerExperience::class,
            'worker_skill' => WorkerSkill::class,
            'worker_language' => WorkerLanguage::class,
            'worker_destination_preference' => WorkerDestinationPreference::class,
            'voice_answer' => VoiceAnswer::class,
            'skill_show' => SkillShow::class,
            'document' => Document::class,
            'evidence_item' => EvidenceItem::class,
            'verification_request' => VerificationRequest::class,
            'consent' => Consent::class,
            'application' => Application::class,
            'application_consent' => ApplicationConsent::class,
            'profile_snapshot' => ProfileSnapshot::class,
            'application_stage_event' => ApplicationStageEvent::class,
            'interview' => Interview::class,
            'offer' => Offer::class,
            'shortlist' => Shortlist::class,
            'shortlist_entry' => ShortlistEntry::class,
            'job_invitation' => JobInvitation::class,
            'conversation' => Conversation::class,
            'message' => Message::class,
            'outbound_message' => OutboundMessage::class,
            'job' => JobPosting::class,
            'job_cost' => JobCost::class,
            'job_mandate' => JobMandate::class,
            'job_skill_requirement' => JobSkillRequirement::class,
            'mobility_case' => MobilityCase::class,
            'mobility_case_stage' => MobilityCaseStage::class,
            'mobility_case_event' => MobilityCaseEvent::class,
            'case_document' => CaseDocument::class,
            'report' => Report::class,
            'course' => Course::class,
            'cohort' => Cohort::class,
            'cohort_member' => CohortMember::class,
            'training_attestation' => TrainingAttestation::class,
            'trade_assessment' => TradeAssessment::class,
            'talent_pool' => TalentPool::class,
            'talent_pool_member' => TalentPoolMember::class,
            'trust_case' => TrustCase::class,
            'trust_case_note' => TrustCaseNote::class,
            'trust_action' => TrustAction::class,
            'appeal' => Appeal::class,
            'campaign' => Campaign::class,
            'lead' => Lead::class,
            'lead_contact_attempt' => LeadContactAttempt::class,
            'recruitment_project' => RecruitmentProject::class,
            'recruitment_project_member' => RecruitmentProjectMember::class,
            'plan' => Plan::class,
            'organization_subscription' => OrganizationSubscription::class,
            'invoice' => Invoice::class,
            'credit_ledger_entry' => CreditLedgerEntry::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
