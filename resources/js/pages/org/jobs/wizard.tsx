import { Form, Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, Plus, Send, Trash2 } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useMemo } from 'react';
import JobController from '@/actions/App/Http/Controllers/Organization/JobController';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { TruthCard } from '@/components/bs/truth-card';
import { ChipGroup } from '@/components/bs/voice-fields-editor';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type Step = 'requirement' | 'skills' | 'terms' | 'mobility' | 'preview';
type FormState = App.Domain.Jobs.Data.JobFormData;
type Options = App.Domain.Jobs.Data.JobFormOptionsData;

type Props = {
    step: Step;
    steps: Step[];
    code: string | null;
    form: FormState | null;
    preview: App.Domain.Jobs.Data.JobTruthCardData | null;
    problems: Record<string, string>;
    live: boolean;
    organizationVerified: boolean;
    agency: boolean;
    options: Options;
};

const BENEFITS = ['accommodation', 'food', 'transport', 'medical', 'airfare'] as const;

type Benefit = (typeof BENEFITS)[number];
type CostRow = {
    category: string;
    description: string;
    amount: string;
    currency: string;
    payer: string;
};
type MobilityForm = Record<
    Benefit | `${Benefit}_details` | 'other_benefits' | 'permit_payer' | 'visa_payer' | 'deadline',
    string
> & {
    costs: CostRow[];
};
const PROVISIONS: App.Domain.Jobs.Enums.BenefitProvision[] = [
    'provided',
    'allowance',
    'deducted',
    'not_provided',
];
const PAYERS: App.Domain.Jobs.Enums.CostPayer[] = ['employer', 'agency', 'worker'];
const PERIODS: App.Domain.Jobs.Enums.SalaryPeriod[] = ['month', 'week', 'day', 'hour'];
const CATEGORIES: App.Domain.Jobs.Enums.JobCostCategory[] = [
    'recruitment_fee',
    'visa',
    'work_permit',
    'medical',
    'skills_test',
    'language_test',
    'airfare',
    'document_attestation',
    'training',
    'accommodation',
    'other',
];

/** The Post a Job wizard: four steps, then preview and submit (prototype "Post job"). */
export default function JobWizard(props: Props) {
    const { t } = useTranslation();
    const { step, steps, code, live } = props;
    const title = code ? t('jobs.wizard.edit_title', { code }) : t('jobs.wizard.new_title');

    return (
        <>
            <Head title={title} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={title}
                    description={live ? t('jobs.wizard.live_note') : t('jobs.wizard.body')}
                    back={{
                        href: code ? JobController.show.url(code) : JobController.index.url(),
                        label: t('jobs.title'),
                    }}
                />

                <nav aria-label={t('jobs.wizard.steps')}>
                    <ol className="flex gap-1 overflow-x-auto pb-1">
                        {steps.map((item, index) => {
                            const current = item === step;
                            const reachable = code !== null || item === 'requirement';
                            const label = `${index + 1}. ${t(`jobs.wizard.step.${item}`)}`;

                            return (
                                <li key={item} className="shrink-0">
                                    {reachable && !current ? (
                                        <Link
                                            href={
                                                code
                                                    ? JobController.edit.url([code, item])
                                                    : JobController.create.url()
                                            }
                                            className="inline-flex min-h-11 items-center rounded-full border border-line px-3 text-xs font-semibold text-muted hover:border-brand/60 hover:text-ink"
                                        >
                                            {label}
                                        </Link>
                                    ) : (
                                        <span
                                            aria-current={current ? 'step' : undefined}
                                            aria-disabled={current ? undefined : true}
                                            className={cn(
                                                'inline-flex min-h-11 items-center rounded-full px-3 text-xs font-semibold',
                                                current
                                                    ? 'bg-brand text-on-brand'
                                                    : 'border border-dashed border-line text-muted',
                                            )}
                                        >
                                            {label}
                                        </span>
                                    )}
                                </li>
                            );
                        })}
                    </ol>
                </nav>

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,420px)] lg:items-start">
                    <div className="min-w-0">
                        {step === 'requirement' && <RequirementStep {...props} />}
                        {step === 'skills' && <SkillsStep {...props} />}
                        {step === 'terms' && <TermsStep {...props} />}
                        {step === 'mobility' && <MobilityStep {...props} />}
                        {step === 'preview' && <PreviewStep {...props} />}
                    </div>
                    {props.preview && step !== 'preview' && (
                        <aside className="flex flex-col gap-2 lg:sticky lg:top-20">
                            <p className="eyebrow text-muted">{t('jobs.wizard.preview_title')}</p>
                            <TruthCard job={props.preview} preview />
                        </aside>
                    )}
                </div>
            </div>
        </>
    );
}

function Card({ children }: { children: ReactNode }) {
    return (
        <div className="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5">
            {children}
        </div>
    );
}

function Field({
    id,
    label,
    error,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

function StepActions({ processing }: { processing: boolean }) {
    const { t } = useTranslation();

    return (
        <Button type="submit" className="min-h-12 self-start sm:min-w-48" disabled={processing}>
            {processing && <Spinner />}
            {t('jobs.wizard.save_continue')}
        </Button>
    );
}

/** Saves a step: creates the draft on the first step, updates it afterwards. */
function useStepSubmit(code: string | null, step: Exclude<Step, 'preview'>) {
    return (form: { post: (url: string) => void; put: (url: string) => void }) =>
        (event: FormEvent) => {
            event.preventDefault();

            if (code) {
                form.put(JobController.update.url([code, step]));
            } else {
                form.post(JobController.store.url());
            }
        };
}

function RequirementStep({ code, form: job, options, agency }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const form = useForm({
        job_mandate_id: job?.jobMandateId ?? '',
        title: job?.title ?? '',
        occupation_id: job?.occupationId ?? '',
        headcount: job?.headcount?.toString() ?? '',
        destination_country_code: job?.destinationCountryCode ?? '',
        destination_city: job?.destinationCity ?? '',
        joining_from: job?.joiningFrom ?? '',
        joining_to: job?.joiningTo ?? '',
    });
    // Employers never send a mandate; agencies post inside one (product rule 10).
    form.transform(({ job_mandate_id, ...data }) => (agency ? { ...data, job_mandate_id } : data));
    const submit = useStepSubmit(code, 'requirement')(form);
    const mandate = options.mandates.find((item) => item.id === form.data.job_mandate_id);
    const occupations =
        mandate && mandate.occupationIds.length > 0
            ? options.occupations.filter((item) => mandate.occupationIds.includes(item.id))
            : options.occupations;
    const destinations = mandate?.destinationCountryCode
        ? options.destinations.filter((country) => country.code === mandate.destinationCountryCode)
        : options.destinations;

    const chooseMandate = (id: string) => {
        const chosen = options.mandates.find((item) => item.id === id);
        form.setData((data) => ({
            ...data,
            job_mandate_id: id,
            destination_country_code:
                chosen?.destinationCountryCode ?? data.destination_country_code,
            occupation_id:
                chosen &&
                chosen.occupationIds.length > 0 &&
                !chosen.occupationIds.includes(data.occupation_id)
                    ? ''
                    : data.occupation_id,
        }));
    };

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                {agency && (
                    <Field
                        id="job_mandate_id"
                        label={t('jobs.field.mandate')}
                        hint={
                            mandate
                                ? t('jobs.mandate.scope', {
                                      employer: mandate.employer,
                                      left: formatNumber(mandate.headcountLeft),
                                      date: mandate.validUntil
                                          ? formatDate(mandate.validUntil)
                                          : '—',
                                  })
                                : t('jobs.field.mandate_hint')
                        }
                        error={form.errors.job_mandate_id}
                    >
                        <NativeSelect
                            id="job_mandate_id"
                            value={form.data.job_mandate_id}
                            onChange={(e) => chooseMandate(e.target.value)}
                        >
                            <option value="" disabled>
                                {t('jobs.mandate.choose')}
                            </option>
                            {options.mandates.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.reference} · {item.employer}
                                    {!item.usable && ` (${t('jobs.mandate.no_longer_usable')})`}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                )}
                <Field
                    id="title"
                    label={t('jobs.field.title')}
                    hint={t('jobs.field.title_hint')}
                    error={form.errors.title}
                >
                    <Input
                        id="title"
                        value={form.data.title}
                        maxLength={120}
                        onChange={(e) => form.setData('title', e.target.value)}
                        className="h-11 text-base"
                    />
                </Field>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field
                        id="occupation_id"
                        label={t('voice.label.occupation')}
                        error={form.errors.occupation_id}
                    >
                        <NativeSelect
                            id="occupation_id"
                            value={form.data.occupation_id}
                            onChange={(e) => form.setData('occupation_id', e.target.value)}
                        >
                            <option value="" disabled>
                                {t('voice.choose_trade')}
                            </option>
                            {occupations.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.name} · {item.sector}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                    <Field
                        id="headcount"
                        label={t('jobs.field.headcount')}
                        error={form.errors.headcount}
                    >
                        <Input
                            id="headcount"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            value={form.data.headcount}
                            onChange={(e) => form.setData('headcount', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                    <Field
                        id="destination_country_code"
                        label={t('jobs.field.destination')}
                        error={form.errors.destination_country_code}
                    >
                        <NativeSelect
                            id="destination_country_code"
                            value={form.data.destination_country_code}
                            onChange={(e) =>
                                form.setData('destination_country_code', e.target.value)
                            }
                        >
                            <option value="" disabled>
                                {t('voice.choose_country')}
                            </option>
                            {destinations.map((country) => (
                                <option key={country.code} value={country.code}>
                                    {country.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                    <Field
                        id="destination_city"
                        label={t('jobs.field.city')}
                        error={form.errors.destination_city}
                    >
                        <Input
                            id="destination_city"
                            value={form.data.destination_city}
                            onChange={(e) => form.setData('destination_city', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                    <Field
                        id="joining_from"
                        label={t('jobs.field.joining_from')}
                        error={form.errors.joining_from}
                    >
                        <Input
                            id="joining_from"
                            type="date"
                            value={form.data.joining_from}
                            onChange={(e) => form.setData('joining_from', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                    <Field
                        id="joining_to"
                        label={t('jobs.field.joining_to')}
                        error={form.errors.joining_to}
                    >
                        <Input
                            id="joining_to"
                            type="date"
                            value={form.data.joining_to}
                            onChange={(e) => form.setData('joining_to', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                </div>
                <StepActions processing={form.processing} />
            </Card>
        </form>
    );
}

function SkillsStep({ code, form: job, options }: Props) {
    const { t } = useTranslation();
    const occupation = options.occupations.find((item) => item.id === job?.occupationId);
    const typical = new Set(occupation?.skills.map((skill) => skill.id) ?? []);
    const skills = [
        ...(occupation?.skills ?? []),
        ...options.skills.filter((skill) => !typical.has(skill.id)),
    ];
    const form = useForm({
        skill_ids: job?.skillIds ?? [],
        min_experience_years: job?.minExperienceYears?.toString() ?? '',
        language_requirements: (job?.languageRequirements ?? []).map((item) => ({
            code: item.code,
            level: item.level,
        })),
    });
    const submit = useStepSubmit(code, 'skills')(form);
    const languageError = Object.entries(form.errors).find(([key]) =>
        key.startsWith('language_requirements'),
    )?.[1];

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1.5 text-sm font-medium">{t('jobs.field.skills')}</legend>
                    <ChipGroup
                        items={skills.map((skill) => ({ value: skill.id, label: skill.name }))}
                        selected={form.data.skill_ids}
                        onToggle={(id) =>
                            form.setData(
                                'skill_ids',
                                form.data.skill_ids.includes(id)
                                    ? form.data.skill_ids.filter((item) => item !== id)
                                    : [...form.data.skill_ids, id],
                            )
                        }
                    />
                    <InputError message={form.errors.skill_ids} />
                </fieldset>
                <Field
                    id="min_experience_years"
                    label={t('jobs.field.min_experience')}
                    error={form.errors.min_experience_years}
                >
                    <Input
                        id="min_experience_years"
                        type="number"
                        inputMode="numeric"
                        min={0}
                        max={40}
                        value={form.data.min_experience_years}
                        onChange={(e) => form.setData('min_experience_years', e.target.value)}
                        className="h-11 max-w-40 text-base"
                    />
                </Field>
                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('jobs.field.languages')}
                    </legend>
                    {form.data.language_requirements.map((row, index) => (
                        <div key={index} className="flex items-end gap-2">
                            <div className="flex w-24 flex-col gap-1">
                                <Label htmlFor={`lang-code-${index}`} className="text-xs">
                                    {t('jobs.field.language_code')}
                                </Label>
                                <Input
                                    id={`lang-code-${index}`}
                                    value={row.code}
                                    maxLength={3}
                                    dir="ltr"
                                    onChange={(e) =>
                                        form.setData(
                                            'language_requirements',
                                            form.data.language_requirements.map((item, i) =>
                                                i === index
                                                    ? {
                                                          ...item,
                                                          code: e.target.value.toLowerCase(),
                                                      }
                                                    : item,
                                            ),
                                        )
                                    }
                                    className="h-11 text-base"
                                />
                            </div>
                            <div className="flex min-w-0 flex-1 flex-col gap-1">
                                <Label htmlFor={`lang-level-${index}`} className="text-xs">
                                    {t('jobs.field.language_level')}
                                </Label>
                                <Input
                                    id={`lang-level-${index}`}
                                    value={row.level}
                                    maxLength={40}
                                    placeholder={t('jobs.field.language_level_example')}
                                    onChange={(e) =>
                                        form.setData(
                                            'language_requirements',
                                            form.data.language_requirements.map((item, i) =>
                                                i === index
                                                    ? { ...item, level: e.target.value }
                                                    : item,
                                            ),
                                        )
                                    }
                                    className="h-11 text-base"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                className="size-11 shrink-0"
                                aria-label={t('worker.languages.remove')}
                                onClick={() =>
                                    form.setData(
                                        'language_requirements',
                                        form.data.language_requirements.filter(
                                            (_, i) => i !== index,
                                        ),
                                    )
                                }
                            >
                                <Trash2 aria-hidden className="size-4" />
                            </Button>
                        </div>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        className="min-h-11 self-start"
                        onClick={() =>
                            form.setData('language_requirements', [
                                ...form.data.language_requirements,
                                { code: 'en', level: '' },
                            ])
                        }
                    >
                        <Plus aria-hidden className="size-4" />
                        {t('worker.languages.add')}
                    </Button>
                    <InputError message={languageError} />
                </fieldset>
                <StepActions processing={form.processing} />
            </Card>
        </form>
    );
}

function TermsStep({ code, form: job, options }: Props) {
    const { t } = useTranslation();
    const destination = options.destinations.find(
        (item) => item.code === job?.destinationCountryCode,
    );
    const form = useForm({
        salary_amount: job?.salaryAmount?.toString() ?? '',
        salary_currency: job?.salaryCurrency ?? destination?.currency ?? '',
        salary_period: (job?.salaryPeriod ?? 'month') as string,
        hours_per_day: job?.hoursPerDay?.toString() ?? '',
        days_per_week: job?.daysPerWeek?.toString() ?? '',
        overtime_terms: job?.overtimeTerms ?? '',
        contract_months: job?.contractMonths?.toString() ?? '',
    });
    const submit = useStepSubmit(code, 'terms')(form);

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                    {t('jobs.field.not_disclosed_note')}
                </p>
                <div className="grid gap-4 sm:grid-cols-3">
                    <Field
                        id="salary_amount"
                        label={t('jobs.field.salary')}
                        error={form.errors.salary_amount}
                    >
                        <Input
                            id="salary_amount"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            value={form.data.salary_amount}
                            onChange={(e) => form.setData('salary_amount', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                    <Field
                        id="salary_currency"
                        label={t('worker.salary.currency')}
                        error={form.errors.salary_currency}
                    >
                        <NativeSelect
                            id="salary_currency"
                            value={form.data.salary_currency}
                            onChange={(e) => form.setData('salary_currency', e.target.value)}
                        >
                            {options.currencies.map((currency) => (
                                <option key={currency} value={currency}>
                                    {currency}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                    <Field
                        id="salary_period"
                        label={t('worker.salary.per')}
                        error={form.errors.salary_period}
                    >
                        <NativeSelect
                            id="salary_period"
                            value={form.data.salary_period}
                            onChange={(e) => form.setData('salary_period', e.target.value)}
                        >
                            {PERIODS.map((period) => (
                                <option key={period} value={period}>
                                    {t(`salary_period.${period}`)}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                    <Field
                        id="hours_per_day"
                        label={t('jobs.field.hours_per_day')}
                        error={form.errors.hours_per_day}
                    >
                        <Input
                            id="hours_per_day"
                            type="number"
                            inputMode="decimal"
                            step="0.5"
                            min={1}
                            max={16}
                            value={form.data.hours_per_day}
                            onChange={(e) => form.setData('hours_per_day', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                    <Field
                        id="days_per_week"
                        label={t('jobs.field.days_per_week')}
                        error={form.errors.days_per_week}
                    >
                        <Input
                            id="days_per_week"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            max={7}
                            value={form.data.days_per_week}
                            onChange={(e) => form.setData('days_per_week', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                    <Field
                        id="contract_months"
                        label={t('jobs.field.contract_months')}
                        error={form.errors.contract_months}
                    >
                        <Input
                            id="contract_months"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            max={120}
                            value={form.data.contract_months}
                            onChange={(e) => form.setData('contract_months', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                </div>
                <Field
                    id="overtime_terms"
                    label={t('jobs.field.overtime')}
                    error={form.errors.overtime_terms}
                >
                    <Input
                        id="overtime_terms"
                        value={form.data.overtime_terms}
                        maxLength={500}
                        onChange={(e) => form.setData('overtime_terms', e.target.value)}
                        className="h-11 text-base"
                    />
                </Field>
                <StepActions processing={form.processing} />
            </Card>
        </form>
    );
}

function MobilityStep({ code, form: job, options }: Props) {
    const { t } = useTranslation();
    const destination = options.destinations.find(
        (item) => item.code === job?.destinationCountryCode,
    );
    const allowed = new Set(destination?.allowedWorkerCosts ?? []);
    const form = useForm<MobilityForm>({
        accommodation: job?.benefits.accommodation ?? '',
        accommodation_details: job?.benefitDetails.accommodation ?? '',
        food: job?.benefits.food ?? '',
        food_details: job?.benefitDetails.food ?? '',
        transport: job?.benefits.transport ?? '',
        transport_details: job?.benefitDetails.transport ?? '',
        medical: job?.benefits.medical ?? '',
        medical_details: job?.benefitDetails.medical ?? '',
        airfare: job?.benefits.airfare ?? '',
        airfare_details: job?.benefitDetails.airfare ?? '',
        other_benefits: job?.otherBenefits ?? '',
        permit_payer: job?.permitPayer ?? '',
        visa_payer: job?.visaPayer ?? '',
        deadline: job?.deadline ?? '',
        costs: (job?.costs ?? []).map((cost) => ({
            category: cost.category,
            description: cost.description ?? '',
            amount: cost.amount.toString(),
            currency: cost.currency,
            payer: cost.payer,
        })),
    });
    const submit = useStepSubmit(code, 'mobility')(form);
    const errors = form.errors as Record<string, string>;
    const setCost = (index: number, key: string, value: string) =>
        form.setData(
            'costs',
            form.data.costs.map((cost, i) => (i === index ? { ...cost, [key]: value } : cost)),
        );

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <fieldset className="flex flex-col gap-3">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('jobs.field.benefits')}
                    </legend>
                    {BENEFITS.map((key) => (
                        <div key={key} className="grid gap-2 sm:grid-cols-[10rem_minmax(0,1fr)]">
                            <Field
                                id={key}
                                label={t(`truth_card.benefit.${key}`)}
                                error={errors[key]}
                            >
                                <NativeSelect
                                    id={key}
                                    value={form.data[key]}
                                    onChange={(e) => form.setData(key, e.target.value)}
                                >
                                    <option value="">{t('common.not_disclosed')}</option>
                                    {PROVISIONS.map((provision) => (
                                        <option key={provision} value={provision}>
                                            {t(`truth_card.provision.${provision}`)}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </Field>
                            <Field
                                id={`${key}_details`}
                                label={t('jobs.field.details')}
                                error={errors[`${key}_details`]}
                            >
                                <Input
                                    id={`${key}_details`}
                                    value={form.data[`${key}_details`]}
                                    maxLength={255}
                                    onChange={(e) => form.setData(`${key}_details`, e.target.value)}
                                    className="h-11 text-base"
                                />
                            </Field>
                        </div>
                    ))}
                </fieldset>
                <Field
                    id="other_benefits"
                    label={t('jobs.field.other_benefits')}
                    error={errors.other_benefits}
                >
                    <Input
                        id="other_benefits"
                        value={form.data.other_benefits}
                        maxLength={1000}
                        onChange={(e) => form.setData('other_benefits', e.target.value)}
                        className="h-11 text-base"
                    />
                </Field>
                <div className="grid gap-4 sm:grid-cols-3">
                    {(['permit_payer', 'visa_payer'] as const).map((key) => (
                        <Field
                            key={key}
                            id={key}
                            label={t(`jobs.field.${key}`)}
                            error={errors[key]}
                        >
                            <NativeSelect
                                id={key}
                                value={form.data[key]}
                                onChange={(e) => form.setData(key, e.target.value)}
                            >
                                <option value="">{t('common.not_disclosed')}</option>
                                {PAYERS.map((payer) => (
                                    <option key={payer} value={payer}>
                                        {t(`truth_card.payer.${payer}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                        </Field>
                    ))}
                    <Field id="deadline" label={t('jobs.field.deadline')} error={errors.deadline}>
                        <Input
                            id="deadline"
                            type="date"
                            value={form.data.deadline}
                            onChange={(e) => form.setData('deadline', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                </div>

                <fieldset className="flex flex-col gap-3">
                    <legend className="mb-1.5 text-sm font-medium">{t('jobs.field.costs')}</legend>
                    <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                        {t('jobs.field.costs_rule', {
                            country: destination?.name ?? '',
                            policy: destination?.workerCostPolicy ?? t('jobs.compliance.no_rules'),
                        })}
                    </p>
                    {form.data.costs.map((cost, index) => {
                        const unlawful =
                            cost.payer === 'worker' &&
                            !allowed.has(cost.category as App.Domain.Jobs.Enums.JobCostCategory);

                        return (
                            <div
                                key={index}
                                className={cn(
                                    'grid gap-2 rounded-xl border p-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_6rem_6rem_8rem_auto] lg:items-end',
                                    unlawful ? 'border-bad/60 bg-bad-soft' : 'border-line',
                                )}
                            >
                                <Field
                                    id={`cost-category-${index}`}
                                    label={t('jobs.field.cost_category')}
                                >
                                    <NativeSelect
                                        id={`cost-category-${index}`}
                                        value={cost.category}
                                        onChange={(e) => setCost(index, 'category', e.target.value)}
                                    >
                                        {CATEGORIES.map((category) => (
                                            <option key={category} value={category}>
                                                {t(`truth_card.cost.${category}`)}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </Field>
                                <Field
                                    id={`cost-description-${index}`}
                                    label={t('jobs.field.cost_description')}
                                >
                                    <Input
                                        id={`cost-description-${index}`}
                                        value={cost.description}
                                        maxLength={160}
                                        onChange={(e) =>
                                            setCost(index, 'description', e.target.value)
                                        }
                                        className="h-11 text-base"
                                    />
                                </Field>
                                <Field
                                    id={`cost-amount-${index}`}
                                    label={t('worker.salary.amount')}
                                >
                                    <Input
                                        id={`cost-amount-${index}`}
                                        type="number"
                                        inputMode="numeric"
                                        min={0}
                                        value={cost.amount}
                                        onChange={(e) => setCost(index, 'amount', e.target.value)}
                                        className="h-11 text-base"
                                    />
                                </Field>
                                <Field
                                    id={`cost-currency-${index}`}
                                    label={t('worker.salary.currency')}
                                >
                                    <NativeSelect
                                        id={`cost-currency-${index}`}
                                        value={cost.currency}
                                        onChange={(e) => setCost(index, 'currency', e.target.value)}
                                    >
                                        {options.currencies.map((currency) => (
                                            <option key={currency} value={currency}>
                                                {currency}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </Field>
                                <Field
                                    id={`cost-payer-${index}`}
                                    label={t('jobs.field.cost_payer')}
                                >
                                    <NativeSelect
                                        id={`cost-payer-${index}`}
                                        value={cost.payer}
                                        onChange={(e) => setCost(index, 'payer', e.target.value)}
                                    >
                                        {PAYERS.map((payer) => (
                                            <option key={payer} value={payer}>
                                                {t(`truth_card.payer.${payer}`)}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </Field>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="size-11"
                                    aria-label={t('jobs.field.remove_cost')}
                                    onClick={() =>
                                        form.setData(
                                            'costs',
                                            form.data.costs.filter((_, i) => i !== index),
                                        )
                                    }
                                >
                                    <Trash2 aria-hidden className="size-4" />
                                </Button>
                                {unlawful && (
                                    <p
                                        role="alert"
                                        className="text-xs font-semibold text-bad sm:col-span-2 lg:col-span-6"
                                    >
                                        {t('jobs.field.cost_not_allowed', {
                                            country: destination?.name ?? '',
                                        })}
                                    </p>
                                )}
                            </div>
                        );
                    })}
                    <Button
                        type="button"
                        variant="outline"
                        className="min-h-11 self-start"
                        onClick={() =>
                            form.setData('costs', [
                                ...form.data.costs,
                                {
                                    category: 'visa',
                                    description: '',
                                    amount: '',
                                    currency: destination?.currency ?? 'AED',
                                    payer: 'employer',
                                },
                            ])
                        }
                    >
                        <Plus aria-hidden className="size-4" />
                        {t('jobs.field.add_cost')}
                    </Button>
                    <InputError
                        message={
                            Object.entries(errors).find(([key]) => key.startsWith('costs'))?.[1]
                        }
                    />
                </fieldset>
                <StepActions processing={form.processing} />
            </Card>
        </form>
    );
}

function PreviewStep({ code, preview, problems, organizationVerified, live }: Props) {
    const { t } = useTranslation();
    const problemList = useMemo(() => Object.entries(problems), [problems]);

    if (!code || !preview) {
        return null;
    }

    return (
        <div className="flex flex-col gap-4">
            <p className="text-sm text-muted">{t('jobs.wizard.preview_body')}</p>
            <TruthCard job={preview} preview />
            {problemList.length > 0 && (
                <div
                    role="alert"
                    className="flex flex-col gap-2 rounded-xl border border-bad/40 bg-bad-soft px-4 py-3 text-sm"
                >
                    <p className="flex items-center gap-2 font-semibold text-bad">
                        <AlertTriangle aria-hidden className="size-4" />
                        {t('jobs.wizard.fix_before_submit')}
                    </p>
                    <ul className="list-disc ps-5">
                        {problemList.map(([field, message]) => (
                            <li key={field}>
                                <span className="font-semibold">
                                    {t(`jobs.problem_field.${field}`, {}, field)}:
                                </span>{' '}
                                {message}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
            {!organizationVerified && (
                <p className="rounded-xl border border-warn/30 bg-warn-soft px-4 py-3 text-sm">
                    {t('jobs.submit.organization_not_verified')}
                </p>
            )}
            {!live && (
                <Form {...JobController.submit.form(code)} className="flex flex-col gap-2">
                    {({ processing, errors }) => (
                        <>
                            <InputError message={errors.job ?? errors.costs} />
                            <Button
                                type="submit"
                                className="min-h-12 self-start sm:min-w-56"
                                disabled={
                                    processing || problemList.length > 0 || !organizationVerified
                                }
                            >
                                {processing ? (
                                    <Spinner />
                                ) : (
                                    <Send aria-hidden className="size-4 rtl:-scale-x-100" />
                                )}
                                {t('jobs.wizard.submit')}
                            </Button>
                            <p className="text-xs text-muted">{t('jobs.wizard.submit_note')}</p>
                        </>
                    )}
                </Form>
            )}
        </div>
    );
}
