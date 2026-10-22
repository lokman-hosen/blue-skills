<?php

use App\Domain\Billing\Actions\IssueInvoice;
use App\Domain\Billing\Enums\CreditReason;
use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\OrganizationSubscription;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\States\Invoice\Issued;
use App\Domain\Billing\States\Invoice\Paid;
use App\Domain\Billing\States\Invoice\Voided;
use App\Domain\Billing\States\Subscription\Ended;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Mail::fake();
    $this->finance = staffUser(StaffRole::FinanceOfficer);
    $this->employer = Organization::factory()->employer()->verified()->create();
    $this->owner = orgMember($this->employer, OrganizationRole::Owner);
});

it('lets finance create plans with exact minor-unit prices', function () {
    $this->actingAs($this->finance)->post(route('admin.billing.plans.store'), [
        'name' => 'Employer Standard', 'organization_type' => 'employer', 'price' => '1500.50', 'price_currency' => 'aed',
        'billing_period' => 'monthly', 'posting_credits' => 10, 'seats' => 5, 'talent_search' => true,
    ])->assertRedirect(route('admin.billing.plans.index'));

    $plan = Plan::query()->sole();
    expect($plan->price_amount_minor)->toBe(150050)
        ->and($plan->price_currency)->toBe('AED')
        ->and($plan->code)->toBe('employer-employer-standard');

    $this->actingAs($this->finance)->post(route('admin.billing.plans.store'), [
        'name' => '', 'organization_type' => 'worker', 'price' => '-1', 'price_currency' => 'AED', 'billing_period' => 'weekly', 'posting_credits' => 1,
    ])->assertSessionHasErrors(['name', 'organization_type', 'price', 'billing_period']);

    $this->actingAs(staffUser(StaffRole::CrmAgent))->get(route('admin.billing.plans.index'))->assertForbidden();
});

it('puts an organization on a plan, invoices it, and adds the credits when paid', function () {
    $old = OrganizationSubscription::factory()->create(['organization_id' => $this->employer->id, 'starts_on' => now()->subMonths(2)->toDateString()]);
    $plan = Plan::factory()->create(['price_amount_minor' => 150_000, 'posting_credits' => 10]);

    $this->actingAs($this->finance)->post(route('admin.billing.organizations.plan', $this->employer), [
        'plan_id' => $plan->id, 'starts_on' => now()->toDateString(), 'create_invoice' => true,
    ])->assertRedirect();

    $invoice = Invoice::query()->sole();
    expect($old->fresh()?->status)->toBeInstanceOf(Ended::class)
        ->and($this->employer->activeSubscription()->first()?->plan_id)->toBe($plan->id)
        ->and($invoice->isDraft())->toBeTrue()
        ->and($invoice->total_minor)->toBe(157_500);

    $this->actingAs($this->finance)->post(route('admin.billing.invoices.lines.store', $invoice), [
        'description' => 'Extra posting credits', 'quantity' => 2, 'unit_amount' => '100', 'credits' => 1,
    ])->assertRedirect();
    expect($invoice->fresh()?->subtotal_minor)->toBe(170_000);

    $this->actingAs($this->finance)->post(route('admin.billing.invoices.issue', $invoice))->assertRedirect();
    $invoice->refresh();
    expect($invoice->status)->toBeInstanceOf(Issued::class)
        ->and($invoice->number)->toBe('INV-'.now()->year.'-00001')
        ->and($invoice->due_on?->toDateString())->toBe(now()->addDays(14)->toDateString())
        ->and($this->owner->notifications()->pluck('type')->all())->toContain('invoice_issued');

    $this->actingAs($this->finance)->post(route('admin.billing.invoices.lines.store', $invoice), [
        'description' => 'Late change', 'quantity' => 1, 'unit_amount' => '1',
    ])->assertSessionHasErrors('invoice');

    $this->actingAs($this->finance)->post(route('admin.billing.invoices.pay', $invoice), [
        'payment_method' => 'bank_transfer', 'payment_reference' => 'FT-88231', 'paid_on' => now()->toDateString(),
    ])->assertRedirect();

    expect($invoice->fresh()?->status)->toBeInstanceOf(Paid::class)
        ->and($this->employer->creditBalance())->toBe(12)
        ->and(CreditLedgerEntry::query()->where('reason', CreditReason::PlanGrant)->sum('delta'))->toBe(10);

    $this->actingAs($this->finance)->post(route('admin.billing.invoices.pay', $invoice), [
        'payment_method' => 'bank_transfer', 'payment_reference' => 'again', 'paid_on' => now()->toDateString(),
    ])->assertSessionHasErrors('invoice');
    $this->actingAs($this->finance)->post(route('admin.billing.invoices.void', $invoice), ['note' => 'Oops'])->assertSessionHasErrors('invoice');
    expect($this->employer->creditBalance())->toBe(12);
});

it('voids an unpaid invoice and adjusts credits with a reason', function () {
    $invoice = Invoice::factory()->issued()->create(['organization_id' => $this->employer->id]);

    $this->actingAs($this->finance)->post(route('admin.billing.invoices.void', $invoice), ['note' => 'Sent to the wrong company'])->assertRedirect();
    expect($invoice->fresh()?->status)->toBeInstanceOf(Voided::class);

    $this->actingAs($this->finance)->post(route('admin.billing.organizations.credits', $this->employer), ['delta' => 3, 'note' => 'Pilot goodwill'])->assertRedirect();
    $this->actingAs($this->finance)->post(route('admin.billing.organizations.credits', $this->employer), ['delta' => 0, 'note' => ''])
        ->assertSessionHasErrors(['delta', 'note']);
    expect($this->employer->creditBalance())->toBe(3);
});

it('shows organizations their plan, credits and issued invoices, never drafts', function () {
    $draft = Invoice::factory()->create(['organization_id' => $this->employer->id]);
    $issued = Invoice::factory()->issued()->create(['organization_id' => $this->employer->id]);
    $issued->lines()->create(['description' => 'Employer Standard', 'quantity' => 1, 'unit_amount_minor' => 1000, 'amount_minor' => 1000]);

    $this->actingAs($this->owner)->get(route('org.billing.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/billing/index')
            ->has('billing.invoices', 1)
            ->where('billing.invoices.0.id', $issued->id)
            ->where('billing.enforced', false));

    $this->actingAs($this->owner)->get(route('org.billing.invoices.show', $issued))
        ->assertInertia(fn (Assert $page) => $page->component('org/billing/invoice')->has('invoice.lines', 1));
    $this->actingAs($this->owner)->get(route('org.billing.invoices.show', $draft))->assertForbidden();
    $this->actingAs(orgMember($this->employer, OrganizationRole::Recruiter))->get(route('org.billing.index'))->assertForbidden();

    $other = Organization::factory()->employer()->verified()->create();
    $this->actingAs(orgMember($other, OrganizationRole::Owner))->get(route('org.billing.invoices.show', $issued))->assertNotFound();
});

it('refuses to issue an empty invoice', function () {
    $invoice = Invoice::factory()->create(['organization_id' => $this->employer->id]);

    expect(fn () => app(IssueInvoice::class)->handle($invoice, $this->finance))
        ->toThrow(ValidationException::class);
});
