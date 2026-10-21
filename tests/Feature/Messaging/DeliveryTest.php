<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Messaging\Channels\CloudWhatsAppSender;
use App\Domain\Messaging\Channels\TwilioSmsSender;
use App\Domain\Messaging\Contracts\SmsSender;
use App\Domain\Messaging\Contracts\WhatsAppSender;
use App\Domain\Messaging\Enums\DeliveryChannel;
use App\Domain\Messaging\Enums\DeliveryStatus;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Mail\NotificationMail;
use App\Domain\Messaging\Models\NotificationPreference;
use App\Domain\Messaging\Models\OutboundMessage;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Messaging\Support\QuietHours;
use App\Domain\Organization\Models\Organization;
use Carbon\CarbonImmutable;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->recruiter = orgMember($this->employer, OrganizationRole::Recruiter);
    $this->worker = makeWorker();
    $this->worker->update(['timezone' => 'Asia/Dhaka', 'locale' => 'bn']);
    $this->application = Application::factory()->create([
        'job_id' => JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id, 'title' => 'Maintenance Electrician']),
        'worker_profile_id' => $this->worker->workerProfile->id,
    ]);

    $this->whatsApp = new class implements WhatsAppSender
    {
        public array $sent = [];

        public bool $fail = false;

        public function sendTemplate(string $phone, string $template, string $locale, array $parameters): string
        {
            if ($this->fail) {
                throw new RuntimeException('WhatsApp unavailable');
            }
            $this->sent[] = compact('phone', 'template', 'locale', 'parameters');

            return 'wamid.1';
        }
    };
    $this->sms = new class implements SmsSender
    {
        public array $sent = [];

        public function send(string $phone, string $text): string
        {
            $this->sent[] = compact('phone', 'text');

            return 'SM1';
        }
    };
    app()->instance(WhatsAppSender::class, $this->whatsApp);
    app()->instance(SmsSender::class, $this->sms);

    // Midday in Dhaka, outside quiet hours.
    $this->travelTo(CarbonImmutable::parse('2026-10-21 06:00', 'UTC'));
});

it('sends worker alerts by WhatsApp in their language with an app link', function () {
    Notify::worker($this->application, NotificationEvent::Shortlisted);

    $outbound = OutboundMessage::query()->sole();
    expect($outbound->status)->toBe(DeliveryStatus::Sent)
        ->and($outbound->channel)->toBe(DeliveryChannel::WhatsApp)
        ->and($outbound->provider_id)->toBe('wamid.1')
        ->and($this->whatsApp->sent[0]['locale'])->toBe('bn')
        ->and($this->whatsApp->sent[0]['phone'])->toBe((string) $this->worker->phone)
        ->and($this->whatsApp->sent[0]['parameters'])->toBe(['Maintenance Electrician', url(route('worker.applications.show', $this->application, false))])
        ->and($this->sms->sent)->toBe([]);
});

it('falls back to SMS when WhatsApp fails, and respects switched-off channels', function () {
    $this->whatsApp->fail = true;
    Notify::worker($this->application, NotificationEvent::OfferReceived);

    expect(OutboundMessage::query()->where('channel', 'whatsapp')->sole()->status)->toBe(DeliveryStatus::Failed)
        ->and(OutboundMessage::query()->where('channel', 'sms')->sole()->status)->toBe(DeliveryStatus::Sent)
        ->and($this->sms->sent[0]['text'])->toContain('BLUESKILLS');

    $this->whatsApp->fail = false;
    NotificationPreference::query()->create(['user_id' => $this->worker->id, 'event' => NotificationEvent::Shortlisted, 'channel' => DeliveryChannel::WhatsApp, 'enabled' => false]);
    Notify::worker($this->application, NotificationEvent::Shortlisted);
    expect(OutboundMessage::query()->latest('id')->first()?->channel)->toBe(DeliveryChannel::Sms);

    NotificationPreference::query()->create(['user_id' => $this->worker->id, 'event' => NotificationEvent::Shortlisted, 'channel' => DeliveryChannel::Sms, 'enabled' => false]);
    $before = OutboundMessage::query()->count();
    Notify::worker($this->application, NotificationEvent::Shortlisted);

    expect(OutboundMessage::query()->count())->toBe($before)
        ->and($this->worker->notifications()->count())->toBe(3);
});

it('holds night-time alerts until 07:00 in the worker\'s time zone', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-21 23:30', 'Asia/Dhaka'));
    Notify::worker($this->application, NotificationEvent::InterviewScheduled, ['at' => now()->addDays(2)->toIso8601String()]);

    $held = OutboundMessage::query()->sole();
    expect($held->status)->toBe(DeliveryStatus::Held)
        ->and($held->send_after?->equalTo(CarbonImmutable::parse('2026-10-22 07:00', 'Asia/Dhaka')))->toBeTrue();

    Artisan::call('notifications:release-held');
    expect($held->fresh()?->status)->toBe(DeliveryStatus::Held);

    $this->travelTo(CarbonImmutable::parse('2026-10-22 07:01', 'Asia/Dhaka'));
    Artisan::call('notifications:release-held');

    expect($held->fresh()?->status)->toBe(DeliveryStatus::Sent)
        ->and($this->whatsApp->sent)->toHaveCount(1);
});

it('knows quiet hours across midnight', function () {
    $worker = makeWorker();
    $worker->update(['timezone' => 'Asia/Kathmandu']);

    expect(QuietHours::releaseAt($worker, CarbonImmutable::parse('2026-10-21 12:00', 'Asia/Kathmandu')))->toBeNull()
        ->and(QuietHours::releaseAt($worker, CarbonImmutable::parse('2026-10-21 05:59', 'Asia/Kathmandu'))?->equalTo(CarbonImmutable::parse('2026-10-21 07:00', 'Asia/Kathmandu')))->toBeTrue()
        ->and(QuietHours::releaseAt($this->recruiter, CarbonImmutable::parse('2026-10-21 23:00', 'Asia/Dubai')))->toBeNull();
});

it('emails organization members unless they switched email off', function () {
    Mail::fake();

    Notify::handlers($this->application, NotificationEvent::ApplicationAnswered, ['answer' => 'withdrawn']);
    Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($this->recruiter->email)
        && str_contains($mail->body, 'withdrew their application for Maintenance Electrician'));

    NotificationPreference::query()->create(['user_id' => $this->recruiter->id, 'event' => NotificationEvent::ApplicationAnswered, 'channel' => DeliveryChannel::Mail, 'enabled' => false]);
    Notify::handlers($this->application, NotificationEvent::ApplicationAnswered, ['answer' => 'declined']);

    Mail::assertSentCount(1);
    expect(OutboundMessage::query()->where('channel', 'whatsapp')->count())->toBe(0);
});

it('sends SMS through Twilio with the sender for the destination country', function () {
    config(['services.twilio' => ['sid' => 'AC123', 'token' => 'secret', 'from' => '+15550001', 'messaging_service_sid' => null, 'senders' => ['BD' => 'BLUESKILLS']]]);
    Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM42'], 201)]);

    expect((new TwilioSmsSender)->send('+8801711000099', 'Hello'))->toBe('SM42')
        ->and((new TwilioSmsSender)->send('+9779801000004', 'Hello'))->toBe('SM42');

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/Accounts/AC123/Messages.json')
        && $request['To'] === '+8801711000099' && $request['From'] === 'BLUESKILLS'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('AC123:secret')));
    Http::assertSent(fn (Request $request) => $request['To'] === '+9779801000004' && $request['From'] === '+15550001');
});

it('sends the approved update template through the WhatsApp Cloud API', function () {
    config(['services.whatsapp.token' => 'token', 'services.whatsapp.phone_number_id' => '123']);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.9']]])]);

    expect((new CloudWhatsAppSender)->sendTemplate('+8801711000099', 'blueskills_update', 'ta', ['Electrician', 'https://blueskills.ae/x']))->toBe('wamid.9');

    Http::assertSent(fn (Request $request) => $request['to'] === '8801711000099'
        && $request['template']['name'] === 'blueskills_update'
        && $request['template']['language']['code'] === 'en'
        && $request['template']['components'][0]['parameters'][1]['text'] === 'https://blueskills.ae/x');
});

it('lets each person choose channels for their own events', function () {
    $this->actingAs($this->worker)->get(route('notifications.preferences'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/preferences')
            ->has('rows', count(NotificationEvent::workerEvents()))
            ->where('rows.0.channels', ['whatsapp' => true, 'sms' => true])
            ->whereNot('quietHours', null));

    $this->put(route('notifications.preferences.update'), ['preferences' => ['shortlisted' => ['whatsapp' => false, 'sms' => true]]])
        ->assertRedirect(route('notifications.preferences'));

    expect(NotificationPreference::query()->where('user_id', $this->worker->id)->where('event', 'shortlisted')->where('channel', 'whatsapp')->value('enabled'))->toBeFalse();

    actingInOrg($this->recruiter, $this->employer)->get(route('notifications.preferences'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows', count(NotificationEvent::organizationEvents()))
            ->where('rows.0.channels', ['mail' => true])
            ->where('quietHours', null));

    $this->put(route('notifications.preferences.update'), ['preferences' => ['new_applicants' => ['mail' => 'maybe']]])
        ->assertSessionHasErrors('preferences.new_applicants.mail');
});
