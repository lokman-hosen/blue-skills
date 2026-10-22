import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Receipt } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { BillingTabs } from '@/components/bs/billing/billing-tabs';
import { EmptyState } from '@/components/bs/empty-state';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Plan = App.Domain.Billing.Data.PlanData;
type Period = App.Domain.Billing.Enums.BillingPeriod;

type Props = { plans: Plan[]; periods: Period[]; currency: string };

/** Plans organizations can be put on (spec §11). Pricing needs approval before launch. */
export default function Plans({ plans, periods, currency }: Props) {
    const { t, formatMoney, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('billing.tab.plans')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('nav.billing')}
                    description={t('billing.plans_body')}
                    actions={<PlanDialog periods={periods} currency={currency} />}
                />
                <BillingTabs active="plans" />
                {plans.length === 0 ? (
                    <EmptyState
                        icon={Receipt}
                        title={t('billing.no_plans')}
                        description={t('billing.no_plans_body')}
                        action={<PlanDialog periods={periods} currency={currency} />}
                    />
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {plans.map((plan) => (
                            <li
                                key={plan.id}
                                className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4 sm:p-5"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <p className="eyebrow text-muted">
                                            {t(`organization_type.${plan.organizationType}`)}
                                        </p>
                                        <h2 className="text-lg font-semibold break-words">
                                            {plan.name}
                                        </h2>
                                    </div>
                                    <PlanDialog periods={periods} currency={currency} plan={plan} />
                                </div>
                                <p className="font-display text-2xl font-bold tnum">
                                    {formatMoney(plan.price)}{' '}
                                    <span className="text-sm font-normal text-muted">
                                        {t(`billing_period.${plan.billingPeriod}`)}
                                    </span>
                                </p>
                                {plan.description && (
                                    <p className="text-sm text-muted">{plan.description}</p>
                                )}
                                <ul className="flex flex-col gap-0.5 text-sm">
                                    <li>
                                        {t('billing.feature.credits', {
                                            count: formatNumber(plan.postingCredits),
                                        })}
                                    </li>
                                    <li>
                                        {plan.seats === null
                                            ? t('billing.seats_unlimited')
                                            : t('billing.feature.seats', {
                                                  count: formatNumber(plan.seats),
                                              })}
                                    </li>
                                    <li>
                                        {plan.talentSearch
                                            ? t('billing.feature.talent_search')
                                            : t('billing.feature.no_talent_search')}
                                    </li>
                                    <li>
                                        {plan.recruitmentProjects
                                            ? t('billing.feature.projects')
                                            : t('billing.feature.no_projects')}
                                    </li>
                                </ul>
                                <div className="mt-auto flex flex-wrap items-center gap-2 pt-1 text-xs text-muted">
                                    <StatusChip
                                        tone={plan.isActive ? 'ok' : 'neutral'}
                                        label={
                                            plan.isActive
                                                ? t('billing.plan_active')
                                                : t('billing.plan_off')
                                        }
                                    />
                                    {t('billing.subscribers', {
                                        count: formatNumber(plan.subscribers),
                                    })}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

function PlanDialog({
    periods,
    currency,
    plan,
}: {
    periods: Period[];
    currency: string;
    plan?: Plan;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const digits =
        new Intl.NumberFormat('en', {
            style: 'currency',
            currency: plan?.price.currency ?? currency,
        }).resolvedOptions().maximumFractionDigits ?? 2;
    const form = useForm({
        name: plan?.name ?? '',
        description: plan?.description ?? '',
        organization_type: (plan?.organizationType ?? 'employer') as string,
        price: plan ? (plan.price.amountMinor / 10 ** digits).toFixed(digits) : '',
        price_currency: plan?.price.currency ?? currency,
        billing_period: (plan?.billingPeriod ?? 'monthly') as string,
        posting_credits: plan ? String(plan.postingCredits) : '0',
        seats: plan?.seats !== null && plan?.seats !== undefined ? String(plan.seats) : '',
        talent_search: plan?.talentSearch ?? false,
        recruitment_projects: plan?.recruitmentProjects ?? false,
        is_active: plan?.isActive ?? true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (plan) {
            form.put(admin.billing.plans.update.url(plan.id), options);
        } else {
            form.post(admin.billing.plans.store.url(), options);
        }
    };

    const check = (key: 'talent_search' | 'recruitment_projects' | 'is_active') => (
        <div className="flex min-h-11 items-center gap-2">
            <Checkbox
                id={`plan-${key}`}
                checked={form.data[key]}
                onCheckedChange={(checked) => form.setData(key, checked === true)}
            />
            <Label htmlFor={`plan-${key}`} className="font-normal">
                {t(`billing.field.${key}`)}
            </Label>
        </div>
    );

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {plan ? (
                    <Button
                        variant="ghost"
                        className="min-h-11"
                        aria-label={t('billing.edit_plan_named', { name: plan.name })}
                    >
                        <Pencil aria-hidden className="size-4" />
                    </Button>
                ) : (
                    <Button className="min-h-11">
                        <Plus aria-hidden className="size-4" />
                        {t('billing.new_plan')}
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-h-[90dvh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {plan ? t('billing.edit_plan') : t('billing.new_plan')}
                    </DialogTitle>
                    <DialogDescription>{t('billing.plan_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="plan-name">{t('billing.field.name')}</Label>
                        <Input
                            id="plan-name"
                            className="h-11"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            aria-invalid={!!form.errors.name}
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="plan-description">{t('billing.field.description')}</Label>
                        <Input
                            id="plan-description"
                            className="h-11"
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                        />
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="plan-type">{t('billing.field.for')}</Label>
                            <NativeSelect
                                id="plan-type"
                                value={form.data.organization_type}
                                onChange={(event) =>
                                    form.setData('organization_type', event.target.value)
                                }
                            >
                                {['employer', 'agency', 'institute'].map((type) => (
                                    <option key={type} value={type}>
                                        {t(`organization_type.${type}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="plan-period">{t('billing.field.period')}</Label>
                            <NativeSelect
                                id="plan-period"
                                value={form.data.billing_period}
                                onChange={(event) =>
                                    form.setData('billing_period', event.target.value)
                                }
                            >
                                {periods.map((period) => (
                                    <option key={period} value={period}>
                                        {t(`billing_period.${period}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                    </div>
                    <div className="grid grid-cols-[minmax(0,1fr)_6rem] gap-3">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="plan-price">{t('billing.field.price')}</Label>
                            <Input
                                id="plan-price"
                                inputMode="decimal"
                                className="h-11"
                                value={form.data.price}
                                onChange={(event) => form.setData('price', event.target.value)}
                                aria-invalid={!!form.errors.price}
                            />
                            <InputError message={form.errors.price} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="plan-currency">{t('billing.field.currency')}</Label>
                            <Input
                                id="plan-currency"
                                className="h-11 uppercase"
                                maxLength={3}
                                value={form.data.price_currency}
                                onChange={(event) =>
                                    form.setData('price_currency', event.target.value.toUpperCase())
                                }
                                aria-invalid={!!form.errors.price_currency}
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="plan-credits">{t('billing.field.credits')}</Label>
                            <Input
                                id="plan-credits"
                                type="number"
                                inputMode="numeric"
                                min={0}
                                className="h-11"
                                value={form.data.posting_credits}
                                onChange={(event) =>
                                    form.setData('posting_credits', event.target.value)
                                }
                                aria-invalid={!!form.errors.posting_credits}
                            />
                            <InputError message={form.errors.posting_credits} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="plan-seats">{t('billing.field.seats')}</Label>
                            <Input
                                id="plan-seats"
                                type="number"
                                inputMode="numeric"
                                min={1}
                                placeholder={t('billing.seats_unlimited')}
                                className="h-11"
                                value={form.data.seats}
                                onChange={(event) => form.setData('seats', event.target.value)}
                                aria-invalid={!!form.errors.seats}
                            />
                            <InputError message={form.errors.seats} />
                        </div>
                    </div>
                    {check('talent_search')}
                    {check('recruitment_projects')}
                    {check('is_active')}
                    <Button
                        type="submit"
                        className="min-h-11 self-start"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        {t('common.save')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
