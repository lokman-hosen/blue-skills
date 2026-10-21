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
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\CountryRule;
use App\Domain\Config\Models\Language;
use App\Domain\Identity\Channels\LogOtpChannel;
use App\Domain\Identity\Channels\WhatsAppCloudOtpChannel;
use App\Domain\Identity\Contracts\OtpChannel;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\StaffInvitation;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\JobSkillRequirement;
use App\Domain\Messaging\Channels\CloudWhatsAppSender;
use App\Domain\Messaging\Channels\LogSmsSender;
use App\Domain\Messaging\Channels\LogWhatsAppSender;
use App\Domain\Messaging\Channels\TwilioSmsSender;
use App\Domain\Messaging\Contracts\SmsSender;
use App\Domain\Messaging\Contracts\WhatsAppSender;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Models\OutboundMessage;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseStage;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationInvitation;
use App\Domain\Organization\Models\OrganizationMember;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Domain\Trust\Models\Report;
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
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
        $this->configureGates();
    }

    protected function configureGates(): void
    {
        Gate::define('viewDesignSystem', fn (User $user): bool => $user->isStaff());
        Gate::define('manageStaff', fn (User $user): bool => $user->hasStaffPermission(Permission::StaffManage->value));
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
            'report' => Report::class,
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
