import { Head, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { BillingSummary } from '@/components/bs/billing/billing-summary';
import { InvoiceTable } from '@/components/bs/billing/invoice-table';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Billing = App.Domain.Billing.Data.OrganizationBillingData;
type Plan = App.Domain.Billing.Data.PlanData;

type Props = { billing: Billing; organizationType: string; plans: Plan[] };

/** One organization's plan, credits and invoices, for finance (spec §11, D-095). */
export default function BillingOrganization({ billing, organizationType, plans }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={billing.organization} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={t(`organization_type.${organizationType}`)}
                    title={billing.organization}
                    back={{ href: admin.billing.index(), label: t('nav.billing') }}
                    actions={
                        <Button
                            className="min-h-11"
                            onClick={() =>
                                router.post(
                                    admin.billing.organizations.invoices.store.url(
                                        billing.organizationId,
                                    ),
                                )
                            }
                        >
                            <Plus aria-hidden className="size-4" />
                            {t('billing.new_invoice')}
                        </Button>
                    }
                />
                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div className="flex min-w-0 flex-col gap-5">
                        <BillingSummary billing={billing} />
                        <section className="flex flex-col gap-3">
                            <h2 className="eyebrow text-muted">{t('billing.invoices')}</h2>
                            <InvoiceTable
                                invoices={billing.invoices}
                                href={(invoice) => admin.billing.invoices.show(invoice.id)}
                            />
                        </section>
                    </div>
                    <div className="flex flex-col gap-5">
                        <AssignPlanForm
                            organizationId={billing.organizationId}
                            plans={plans}
                            currentPlanId={billing.plan?.id ?? null}
                        />
                        <AdjustCreditsForm organizationId={billing.organizationId} />
                    </div>
                </div>
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-3 self-start rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="eyebrow text-muted">{title}</h2>
            {children}
        </section>
    );
}

function AssignPlanForm({
    organizationId,
    plans,
    currentPlanId,
}: {
    organizationId: string;
    plans: Plan[];
    currentPlanId: string | null;
}) {
    const { t, formatMoney } = useTranslation();
    const form = useForm({
        plan_id: plans.find((plan) => plan.id !== currentPlanId)?.id ?? plans[0]?.id ?? '',
        starts_on: new Date().toISOString().slice(0, 10),
        create_invoice: true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(admin.billing.organizations.plan.url(organizationId), { preserveScroll: true });
    };

    return (
        <Panel title={t('billing.assign_plan')}>
            {plans.length === 0 ? (
                <p className="text-sm text-muted">{t('billing.no_plans_for_type')}</p>
            ) : (
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="assign-plan">{t('billing.plan')}</Label>
                        <NativeSelect
                            id="assign-plan"
                            value={form.data.plan_id}
                            onChange={(event) => form.setData('plan_id', event.target.value)}
                            aria-invalid={!!form.errors.plan_id}
                        >
                            {plans.map((plan) => (
                                <option key={plan.id} value={plan.id}>
                                    {plan.name} · {formatMoney(plan.price)}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={form.errors.plan_id} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="assign-start">{t('billing.starts_on')}</Label>
                        <Input
                            id="assign-start"
                            type="date"
                            className="h-11"
                            value={form.data.starts_on}
                            onChange={(event) => form.setData('starts_on', event.target.value)}
                            aria-invalid={!!form.errors.starts_on}
                        />
                        <InputError message={form.errors.starts_on} />
                    </div>
                    <div className="flex min-h-11 items-center gap-2">
                        <Checkbox
                            id="assign-invoice"
                            checked={form.data.create_invoice}
                            onCheckedChange={(checked) =>
                                form.setData('create_invoice', checked === true)
                            }
                        />
                        <Label htmlFor="assign-invoice" className="font-normal">
                            {t('billing.with_invoice')}
                        </Label>
                    </div>
                    <p className="text-xs text-muted">{t('billing.assign_hint')}</p>
                    <Button
                        type="submit"
                        className="min-h-11 self-start"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        {t('billing.assign')}
                    </Button>
                </form>
            )}
        </Panel>
    );
}

function AdjustCreditsForm({ organizationId }: { organizationId: string }) {
    const { t } = useTranslation();
    const form = useForm({ delta: '', note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(admin.billing.organizations.credits.url(organizationId), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <Panel title={t('billing.adjust_credits')}>
            <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="credits-delta">{t('billing.credits_change')}</Label>
                    <Input
                        id="credits-delta"
                        type="number"
                        inputMode="numeric"
                        className="h-11"
                        value={form.data.delta}
                        onChange={(event) => form.setData('delta', event.target.value)}
                        aria-invalid={!!form.errors.delta}
                        aria-describedby="credits-delta-hint"
                    />
                    <p id="credits-delta-hint" className="text-xs text-muted">
                        {t('billing.credits_change_hint')}
                    </p>
                    <InputError message={form.errors.delta} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="credits-note">{t('billing.credits_note')}</Label>
                    <Input
                        id="credits-note"
                        className="h-11"
                        value={form.data.note}
                        onChange={(event) => form.setData('note', event.target.value)}
                        aria-invalid={!!form.errors.note}
                    />
                    <InputError message={form.errors.note} />
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    className="min-h-11 self-start"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    {t('billing.save_credits')}
                </Button>
            </form>
        </Panel>
    );
}
