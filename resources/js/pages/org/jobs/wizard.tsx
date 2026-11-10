import { Form, Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, Plus, Send, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import JobController from '@/actions/App/Http/Controllers/Organization/JobController';
import {
    Field,
    OptionSelect,
    OtherInput,
    TextArea,
    WizardCard as Card,
} from '@/components/bs/jobs/wizard-fields';
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

type Step = 'basics' | 'pay' | 'requirements' | 'apply' | 'preview';
type FormState = App.Domain.Jobs.Data.JobFormData;
type Options = App.Domain.Jobs.Data.JobFormOptionsData;
type Errors = Record<string, string>;

type Props = {
    step: Step;
    steps: Step[];
    code: string | null;
    form: FormState | null;
    preview: App.Domain.Jobs.Data.JobTruthCardData | null;
    problems: Record<string, string>;
    live: boolean;
    organizationVerified: boolean;
    organizationName: string;
    agency: boolean;
    options: Options;
};

/** Accommodation first: it is the only benefit with a follow-up field (its monthly cost). */
const BENEFITS = ['accommodation', 'food', 'transport', 'insurance', 'medical', 'airfare'] as const;
type Benefit = (typeof BENEFITS)[number];

/** Free, Paid, Not provided (prototype), plus Allowance for jobs that use it (D-117). */
const PROVISIONS: App.Domain.Jobs.Enums.BenefitProvision[] = [
    'provided',
    'deducted',
    'not_provided',
    'allowance',
];
const PAYERS: App.Domain.Jobs.Enums.CostPayer[] = ['employer', 'agency', 'worker'];
const PERIODS: App.Domain.Jobs.Enums.SalaryPeriod[] = ['hour', 'day', 'week', 'month', 'year'];
const LANGUAGE_LEVELS = ['basic', 'communicative', 'fluent'];
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

type CostRow = {
    category: string;
    description: string;
    amount: string;
    currency: string;
    payer: string;
};

/** The Post a Job wizard: four steps, then preview and submit (job posting prototype, D-117). */
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
                    {/* Scrolls sideways on phones; focusable so keyboard users can scroll it (WCAG 2.1.1). */}
                    <ol
                        tabIndex={0}
                        className="flex gap-1 overflow-x-auto rounded-lg pb-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                    >
                        {steps.map((item, index) => {
                            const current = item === step;
                            const reachable = code !== null || item === 'basics';
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
                        {step === 'basics' && <BasicsStep {...props} />}
                        {step === 'pay' && <PayStep {...props} />}
                        {step === 'requirements' && <RequirementsStep {...props} />}
                        {step === 'apply' && <ApplyStep {...props} />}
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

function StepActions({ processing }: { processing: boolean }) {
    const { t } = useTranslation();

    return (
        <Button type="submit" className="min-h-12 self-start sm:min-w-48" disabled={processing}>
            {processing && <Spinner />}
            {t('jobs.wizard.save_continue')}
        </Button>
    );
}

type StepForm = {
    post: (url: string) => void;
    put: (url: string) => void;
    setError: (errors: Errors) => void;
    clearErrors: () => void;
};

/**
 * Saves a step: creates the draft on the first step, updates it afterwards. The client checks
 * repeat the server's cross-field rules so the poster sees them before sending (D-117); the
 * server still validates everything.
 */
function useStepSubmit(code: string | null, step: Exclude<Step, 'preview'>) {
    return (form: StepForm, check: () => Errors = () => ({})) =>
        (event: FormEvent) => {
            event.preventDefault();
            const errors = check();

            if (Object.keys(errors).length > 0) {
                form.clearErrors();
                form.setError(errors);

                return;
            }

            if (code) {
                form.put(JobController.update.url([code, step]));
            } else {
                form.post(JobController.store.url());
            }
        };
}

/** "Other" needs the poster's own words (server rule `required_if:…,other`). */
function requireOther(
    errors: Errors,
    message: string,
    pairs: [value: string, other: string, key: string][],
) {
    pairs.forEach(([value, other, key]) => {
        if (value === 'other' && other.trim() === '') {
            errors[key] = message;
        }
    });
}

function BasicsStep({ code, form: job, options, agency, organizationName }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const form = useForm({
        job_mandate_id: job?.jobMandateId ?? '',
        title: job?.title ?? '',
        occupation_id: job?.occupationId ?? '',
        employment_type: (job?.employmentType ?? '') as string,
        headcount: job?.headcount?.toString() ?? '',
        destination_country_code: job?.destinationCountryCode ?? '',
        destination_city: job?.destinationCity ?? '',
        joining_from: job?.joiningFrom ?? '',
        joining_to: job?.joiningTo ?? '',
    });
    // Employers never send a mandate; agencies post inside one (product rule 10).
    form.transform(({ job_mandate_id, ...data }) => (agency ? { ...data, job_mandate_id } : data));
    const submit = useStepSubmit(code, 'basics')(form);
    const mandate = options.mandates.find((item) => item.id === form.data.job_mandate_id);
    const allowed =
        mandate && mandate.occupationIds.length > 0
            ? options.occupations.filter((item) => mandate.occupationIds.includes(item.id))
            : options.occupations;
    const industries = [
        ...new Map(allowed.map((item) => [item.sectorSlug, item.sector])).entries(),
    ];
    // Industry is the trade's sector: it narrows the trade list and is never stored on its own.
    const [industry, setIndustry] = useState(
        () => options.occupations.find((item) => item.id === job?.occupationId)?.sectorSlug ?? '',
    );
    const occupations = industry ? allowed.filter((item) => item.sectorSlug === industry) : allowed;
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

    const chooseIndustry = (slug: string) => {
        setIndustry(slug);
        const current = options.occupations.find((item) => item.id === form.data.occupation_id);

        if (slug && current?.sectorSlug !== slug) {
            form.setData('occupation_id', '');
        }
    };

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <dl className="grid gap-3 rounded-xl bg-surface-2 px-4 py-3 text-sm sm:grid-cols-2">
                    <div className="min-w-0">
                        <dt className="text-xs text-muted">{t('jobs.wizard.posting_as')}</dt>
                        <dd className="font-semibold break-words">
                            {t(
                                agency
                                    ? 'jobs.wizard.posting_as_agency'
                                    : 'jobs.wizard.posting_as_employer',
                                { name: organizationName },
                            )}
                        </dd>
                    </div>
                    <div className="min-w-0">
                        <dt className="text-xs text-muted">{t('truth_card.row.employer')}</dt>
                        <dd className="font-semibold break-words">
                            {agency
                                ? (mandate?.employer ?? (
                                      <span className="font-normal text-muted">
                                          {t('jobs.wizard.employer_from_mandate')}
                                      </span>
                                  ))
                                : organizationName}
                        </dd>
                    </div>
                </dl>
                {agency && (
                    <Field
                        id="job_mandate_id"
                        required
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
                            aria-required
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
                    required
                    label={t('jobs.field.title')}
                    hint={t('jobs.field.title_hint')}
                    error={form.errors.title}
                >
                    <Input
                        placeholder={t('jobs.field.title_placeholder')}
                        aria-required
                        id="title"
                        value={form.data.title}
                        maxLength={120}
                        onChange={(e) => form.setData('title', e.target.value)}
                        className="h-11 text-base"
                    />
                </Field>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field
                        id="destination_country_code"
                        required
                        label={t('jobs.field.destination')}
                        error={form.errors.destination_country_code}
                    >
                        <NativeSelect
                            aria-required
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
                            placeholder={t('jobs.field.city_placeholder')}
                            id="destination_city"
                            value={form.data.destination_city}
                            maxLength={120}
                            onChange={(e) => form.setData('destination_city', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                    <Field
                        id="headcount"
                        required
                        label={t('jobs.field.headcount')}
                        error={form.errors.headcount}
                    >
                        <Input
                            placeholder={t('jobs.field.headcount_placeholder')}
                            aria-required
                            id="headcount"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            max={10000}
                            value={form.data.headcount}
                            onChange={(e) => form.setData('headcount', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                    <Field id="industry" label={t('jobs.field.industry')}>
                        <NativeSelect
                            id="industry"
                            value={industry}
                            onChange={(e) => chooseIndustry(e.target.value)}
                        >
                            <option value="">{t('jobs.field.industry_choose')}</option>
                            {industries.map(([slug, name]) => (
                                <option key={slug} value={slug}>
                                    {name}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                    <Field
                        id="occupation_id"
                        required
                        label={t('voice.label.occupation')}
                        error={form.errors.occupation_id}
                    >
                        <NativeSelect
                            aria-required
                            id="occupation_id"
                            value={form.data.occupation_id}
                            onChange={(e) => form.setData('occupation_id', e.target.value)}
                        >
                            <option value="" disabled>
                                {t('voice.choose_trade')}
                            </option>
                            {occupations.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {industry ? item.name : `${item.name} · ${item.sector}`}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                    <OptionSelect
                        id="employment_type"
                        option="employment_type"
                        label={t('jobs.field.employment_type')}
                        value={form.data.employment_type}
                        onChange={(value) => form.setData('employment_type', value)}
                        error={form.errors.employment_type}
                    />
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
                <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                    {t('jobs.wizard.campaign_note')}
                </p>
                <StepActions processing={form.processing} />
            </Card>
        </form>
    );
}

type PayForm = Record<
    | 'salary_amount'
    | 'salary_max_amount'
    | 'salary_currency'
    | 'salary_period'
    | 'salary_basis'
    | 'working_hours'
    | 'working_hours_other'
    | 'working_days'
    | 'working_days_other'
    | 'shift_pattern'
    | 'shift_pattern_other'
    | 'overtime_terms'
    | Benefit
    | `${Benefit}_details`
    | 'accommodation_cost_amount'
    | 'other_benefits',
    string
>;

function PayStep({ code, form: job, options }: Props) {
    const { t } = useTranslation();
    const destination = options.destinations.find(
        (item) => item.code === job?.destinationCountryCode,
    );
    const form = useForm<PayForm>({
        salary_amount: job?.salaryAmount?.toString() ?? '',
        salary_max_amount: job?.salaryMaxAmount?.toString() ?? '',
        salary_currency: job?.salaryCurrency ?? destination?.currency ?? '',
        salary_period: job?.salaryPeriod ?? 'month',
        salary_basis: job?.salaryBasis ?? '',
        working_hours: job?.workingHours ?? '',
        working_hours_other: job?.workingHoursOther ?? '',
        working_days: job?.workingDays ?? '',
        working_days_other: job?.workingDaysOther ?? '',
        shift_pattern: job?.shiftPattern ?? '',
        shift_pattern_other: job?.shiftPatternOther ?? '',
        overtime_terms: job?.overtimeTerms ?? '',
        accommodation: job?.benefits.accommodation ?? '',
        accommodation_details: job?.benefitDetails.accommodation ?? '',
        accommodation_cost_amount: job?.accommodationCostAmount?.toString() ?? '',
        food: job?.benefits.food ?? '',
        food_details: job?.benefitDetails.food ?? '',
        transport: job?.benefits.transport ?? '',
        transport_details: job?.benefitDetails.transport ?? '',
        insurance: job?.benefits.insurance ?? '',
        insurance_details: job?.benefitDetails.insurance ?? '',
        medical: job?.benefits.medical ?? '',
        medical_details: job?.benefitDetails.medical ?? '',
        airfare: job?.benefits.airfare ?? '',
        airfare_details: job?.benefitDetails.airfare ?? '',
        other_benefits: job?.otherBenefits ?? '',
    });
    const data = form.data;
    const errors = form.errors as Errors;
    const paysForAccommodation = data.accommodation === 'deducted';

    const check = () => {
        const found: Errors = {};
        requireOther(found, t('jobs.validation.other_required'), [
            [data.working_hours, data.working_hours_other, 'working_hours_other'],
            [data.working_days, data.working_days_other, 'working_days_other'],
            [data.shift_pattern, data.shift_pattern_other, 'shift_pattern_other'],
        ]);

        if (data.salary_max_amount !== '' && data.salary_amount === '') {
            found.salary_amount = t('jobs.validation.salary_min_required');
        } else if (
            data.salary_max_amount !== '' &&
            Number(data.salary_max_amount) < Number(data.salary_amount)
        ) {
            found.salary_max_amount = t('jobs.validation.salary_max_below_min');
        }

        if (paysForAccommodation && data.accommodation_cost_amount === '') {
            found.accommodation_cost_amount = t('jobs.validation.accommodation_cost_required');
        }

        if (
            (data.salary_amount !== '' ||
                (paysForAccommodation && data.accommodation_cost_amount !== '')) &&
            data.salary_currency === ''
        ) {
            found.salary_currency = t('jobs.validation.currency_required');
        }

        return found;
    };
    const submit = useStepSubmit(code, 'pay')(form, check);

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                    {t('jobs.field.not_disclosed_note')}
                </p>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field
                        id="salary_currency"
                        required={
                            data.salary_amount !== '' ||
                            (paysForAccommodation && data.accommodation_cost_amount !== '')
                        }
                        label={t('worker.salary.currency')}
                        error={errors.salary_currency}
                    >
                        <NativeSelect
                            aria-required={
                                data.salary_amount !== '' ||
                                (paysForAccommodation && data.accommodation_cost_amount !== '')
                            }
                            id="salary_currency"
                            value={data.salary_currency}
                            onChange={(e) => form.setData('salary_currency', e.target.value)}
                        >
                            <option value="" disabled>
                                {t('worker.salary.currency')}
                            </option>
                            {options.currencies.map((currency) => (
                                <option key={currency} value={currency}>
                                    {currency}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                    <Field
                        id="salary_period"
                        required={data.salary_amount !== ''}
                        label={t('jobs.field.salary_period')}
                        error={errors.salary_period}
                    >
                        <NativeSelect
                            aria-required={data.salary_amount !== ''}
                            id="salary_period"
                            value={data.salary_period}
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
                        id="salary_amount"
                        required={data.salary_max_amount !== ''}
                        label={t('jobs.field.salary_min')}
                        error={errors.salary_amount}
                    >
                        <Input
                            placeholder={t('jobs.field.salary_placeholder')}
                            aria-required={data.salary_max_amount !== ''}
                            id="salary_amount"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            value={data.salary_amount}
                            onChange={(e) => form.setData('salary_amount', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                    <Field
                        id="salary_max_amount"
                        label={t('jobs.field.salary_max')}
                        hint={t('jobs.field.salary_max_hint')}
                        error={errors.salary_max_amount}
                    >
                        <Input
                            placeholder={t('jobs.field.salary_max_placeholder')}
                            id="salary_max_amount"
                            type="number"
                            inputMode="numeric"
                            min={data.salary_amount || 1}
                            aria-describedby="salary_max_amount-hint"
                            value={data.salary_max_amount}
                            onChange={(e) => form.setData('salary_max_amount', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                </div>
                <OptionSelect
                    id="salary_basis"
                    option="salary_basis"
                    label={t('jobs.field.salary_basis')}
                    value={data.salary_basis}
                    onChange={(value) => form.setData('salary_basis', value)}
                    error={errors.salary_basis}
                />
                <div className="grid gap-4 sm:grid-cols-2">
                    {(['working_hours', 'working_days'] as const).map((key) => (
                        <div key={key} className="flex min-w-0 flex-col gap-3">
                            <OptionSelect
                                id={key}
                                option={key}
                                label={t(`jobs.field.${key}`)}
                                value={data[key]}
                                onChange={(value) => form.setData(key, value)}
                                error={errors[key]}
                            />
                            {data[key] === 'other' && (
                                <OtherInput
                                    id={`${key}_other`}
                                    value={data[`${key}_other`]}
                                    onChange={(value) => form.setData(`${key}_other`, value)}
                                    error={errors[`${key}_other`]}
                                />
                            )}
                        </div>
                    ))}
                </div>
                <OptionSelect
                    id="shift_pattern"
                    option="shift_pattern"
                    label={t('jobs.field.shift_pattern')}
                    value={data.shift_pattern}
                    onChange={(value) => form.setData('shift_pattern', value)}
                    error={errors.shift_pattern}
                />
                {data.shift_pattern === 'other' && (
                    <OtherInput
                        id="shift_pattern_other"
                        value={data.shift_pattern_other}
                        onChange={(value) => form.setData('shift_pattern_other', value)}
                        error={errors.shift_pattern_other}
                    />
                )}
                <Field
                    id="overtime_terms"
                    label={t('jobs.field.overtime')}
                    error={errors.overtime_terms}
                >
                    <Input
                        placeholder={t('jobs.field.overtime_placeholder')}
                        id="overtime_terms"
                        value={data.overtime_terms}
                        maxLength={500}
                        onChange={(e) => form.setData('overtime_terms', e.target.value)}
                        className="h-11 text-base"
                    />
                </Field>

                <fieldset className="flex flex-col gap-3">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('jobs.field.benefits')}
                    </legend>
                    {BENEFITS.map((key) => (
                        <div
                            key={key}
                            className="grid gap-2 border-b border-line/70 pb-3 last:border-0 sm:grid-cols-[12rem_minmax(0,1fr)]"
                        >
                            <Field
                                id={key}
                                label={t(`truth_card.benefit.${key}`)}
                                error={errors[key]}
                            >
                                <NativeSelect
                                    id={key}
                                    value={data[key]}
                                    onChange={(e) => form.setData(key, e.target.value)}
                                >
                                    {PROVISIONS.map((provision) => (
                                        <option key={provision} value={provision}>
                                            {t(`truth_card.provision.${provision}`)}
                                        </option>
                                    ))}
                                    <option value="">{t('jobs.field.unknown')}</option>
                                </NativeSelect>
                            </Field>
                            <Field
                                id={`${key}_details`}
                                label={t('jobs.field.details')}
                                error={errors[`${key}_details`]}
                            >
                                <Input
                                    placeholder={t('jobs.field.details_placeholder')}
                                    id={`${key}_details`}
                                    value={data[`${key}_details`]}
                                    maxLength={255}
                                    onChange={(e) => form.setData(`${key}_details`, e.target.value)}
                                    className="h-11 text-base"
                                />
                            </Field>
                            {key === 'accommodation' && paysForAccommodation && (
                                <div className="sm:col-start-2">
                                    <Field
                                        id="accommodation_cost_amount"
                                        required
                                        label={t('jobs.field.accommodation_cost')}
                                        hint={t('jobs.field.accommodation_cost_hint', {
                                            currency: data.salary_currency || '—',
                                        })}
                                        error={errors.accommodation_cost_amount}
                                    >
                                        <Input
                                            placeholder={t(
                                                'jobs.field.accommodation_cost_placeholder',
                                            )}
                                            aria-required
                                            id="accommodation_cost_amount"
                                            type="number"
                                            inputMode="numeric"
                                            min={1}
                                            aria-describedby="accommodation_cost_amount-hint"
                                            value={data.accommodation_cost_amount}
                                            onChange={(e) =>
                                                form.setData(
                                                    'accommodation_cost_amount',
                                                    e.target.value,
                                                )
                                            }
                                            className="h-11 max-w-48 text-base"
                                        />
                                    </Field>
                                </div>
                            )}
                        </div>
                    ))}
                </fieldset>
                <Field
                    id="other_benefits"
                    label={t('jobs.field.other_benefits')}
                    error={errors.other_benefits}
                >
                    <Input
                        placeholder={t('jobs.field.other_benefits_placeholder')}
                        id="other_benefits"
                        value={data.other_benefits}
                        maxLength={1000}
                        onChange={(e) => form.setData('other_benefits', e.target.value)}
                        className="h-11 text-base"
                    />
                </Field>
                <StepActions processing={form.processing} />
            </Card>
        </form>
    );
}

function RequirementsStep({ code, form: job, options }: Props) {
    const { t } = useTranslation();
    const occupation = options.occupations.find((item) => item.id === job?.occupationId);
    const typical = new Set(occupation?.skills.map((skill) => skill.id) ?? []);
    const skills = [
        ...(occupation?.skills ?? []),
        ...options.skills.filter((skill) => !typical.has(skill.id)),
    ];
    const form = useForm({
        experience_level: (job?.experienceLevel ?? '') as string,
        experience_other: job?.experienceOther ?? '',
        education_level: (job?.educationLevel ?? '') as string,
        education_other: job?.educationOther ?? '',
        language_requirements: (job?.languageRequirements ?? []).map((item) => ({
            code: item.code,
            level: item.level,
        })),
        age_range: (job?.ageRange ?? '') as string,
        age_other: job?.ageOther ?? '',
        skill_ids: job?.skillIds ?? [],
        other_skills: job?.otherSkills ?? '',
        duties: job?.duties ?? '',
    });
    const data = form.data;
    const errors = form.errors as Errors;
    const check = () => {
        const found: Errors = {};
        requireOther(found, t('jobs.validation.other_required'), [
            [data.experience_level, data.experience_other, 'experience_other'],
            [data.education_level, data.education_other, 'education_other'],
            [data.age_range, data.age_other, 'age_other'],
        ]);

        return found;
    };
    const submit = useStepSubmit(code, 'requirements')(form, check);
    const languageError = Object.entries(errors).find(([key]) =>
        key.startsWith('language_requirements'),
    )?.[1];
    const setLanguage = (index: number, change: Partial<{ code: string; level: string }>) =>
        form.setData(
            'language_requirements',
            data.language_requirements.map((item, i) =>
                i === index ? { ...item, ...change } : item,
            ),
        );

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <div className="grid gap-4 sm:grid-cols-2">
                    {(
                        [
                            ['experience_level', 'experience_other', 'jobs.field.experience'],
                            ['education_level', 'education_other', 'jobs.field.education'],
                        ] as const
                    ).map(([key, other, label]) => (
                        <div key={key} className="flex min-w-0 flex-col gap-3">
                            <OptionSelect
                                id={key}
                                option={key}
                                label={t(label)}
                                value={data[key]}
                                onChange={(value) => form.setData(key, value)}
                                error={errors[key]}
                            />
                            {data[key] === 'other' && (
                                <OtherInput
                                    id={other}
                                    value={data[other]}
                                    onChange={(value) => form.setData(other, value)}
                                    error={errors[other]}
                                />
                            )}
                        </div>
                    ))}
                </div>

                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('jobs.field.languages')}
                    </legend>
                    {data.language_requirements.map((row, index) => {
                        const preset = LANGUAGE_LEVELS.includes(row.level);

                        return (
                            <div
                                key={index}
                                className="flex flex-wrap items-end gap-2 sm:flex-nowrap"
                            >
                                <div className="flex w-24 flex-col gap-1">
                                    <Label
                                        htmlFor={`lang-code-${index}`}
                                        className="text-xs"
                                        required
                                    >
                                        {t('jobs.field.language_code')}
                                    </Label>
                                    <Input
                                        placeholder={t('jobs.field.language_code_placeholder')}
                                        id={`lang-code-${index}`}
                                        aria-required
                                        value={row.code}
                                        maxLength={3}
                                        dir="ltr"
                                        onChange={(e) =>
                                            setLanguage(index, {
                                                code: e.target.value.toLowerCase(),
                                            })
                                        }
                                        className="h-11 text-base"
                                    />
                                </div>
                                <div className="flex min-w-0 flex-1 flex-col gap-1">
                                    <Label
                                        htmlFor={`lang-level-${index}`}
                                        className="text-xs"
                                        required
                                    >
                                        {t('jobs.field.language_level')}
                                    </Label>
                                    <NativeSelect
                                        id={`lang-level-${index}`}
                                        aria-required
                                        value={preset ? row.level : 'other'}
                                        onChange={(e) =>
                                            setLanguage(index, {
                                                level:
                                                    e.target.value === 'other'
                                                        ? ''
                                                        : e.target.value,
                                            })
                                        }
                                    >
                                        {[...LANGUAGE_LEVELS, 'other'].map((level) => (
                                            <option key={level} value={level}>
                                                {t(`jobs.language_level.${level}`)}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </div>
                                {!preset && (
                                    <div className="flex min-w-0 flex-1 flex-col gap-1">
                                        <Label
                                            htmlFor={`lang-level-other-${index}`}
                                            className="text-xs"
                                            required
                                        >
                                            {t('jobs.field.other_details')}
                                        </Label>
                                        <Input
                                            id={`lang-level-other-${index}`}
                                            aria-required
                                            value={row.level}
                                            maxLength={40}
                                            placeholder={t('jobs.field.language_level_example')}
                                            onChange={(e) =>
                                                setLanguage(index, { level: e.target.value })
                                            }
                                            className="h-11 text-base"
                                        />
                                    </div>
                                )}
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="size-11 shrink-0"
                                    aria-label={t('worker.languages.remove')}
                                    onClick={() =>
                                        form.setData(
                                            'language_requirements',
                                            data.language_requirements.filter(
                                                (_, i) => i !== index,
                                            ),
                                        )
                                    }
                                >
                                    <Trash2 aria-hidden className="size-4" />
                                </Button>
                            </div>
                        );
                    })}
                    <Button
                        type="button"
                        variant="outline"
                        className="min-h-11 self-start"
                        onClick={() =>
                            form.setData('language_requirements', [
                                ...data.language_requirements,
                                { code: 'en', level: 'basic' },
                            ])
                        }
                    >
                        <Plus aria-hidden className="size-4" />
                        {t('worker.languages.add')}
                    </Button>
                    <InputError message={languageError} />
                </fieldset>

                <OptionSelect
                    id="age_range"
                    option="age_range"
                    label={t('jobs.field.age')}
                    hint={t('jobs.field.age_hint')}
                    value={data.age_range}
                    onChange={(value) => form.setData('age_range', value)}
                    error={errors.age_range}
                />
                {data.age_range === 'other' && (
                    <OtherInput
                        id="age_other"
                        value={data.age_other}
                        onChange={(value) => form.setData('age_other', value)}
                        error={errors.age_other}
                    />
                )}

                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1.5 text-sm font-medium">{t('jobs.field.skills')}</legend>
                    <ChipGroup
                        items={skills.map((skill) => ({ value: skill.id, label: skill.name }))}
                        selected={data.skill_ids}
                        onToggle={(id) =>
                            form.setData(
                                'skill_ids',
                                data.skill_ids.includes(id)
                                    ? data.skill_ids.filter((item) => item !== id)
                                    : [...data.skill_ids, id],
                            )
                        }
                    />
                    <InputError message={errors.skill_ids} />
                </fieldset>
                <Field
                    id="other_skills"
                    label={t('jobs.field.other_skills')}
                    hint={t('jobs.field.other_skills_hint')}
                    error={errors.other_skills}
                >
                    <Input
                        id="other_skills"
                        value={data.other_skills}
                        maxLength={500}
                        placeholder={t('jobs.field.other_skills_placeholder')}
                        aria-describedby="other_skills-hint"
                        onChange={(e) => form.setData('other_skills', e.target.value)}
                        className="h-11 text-base"
                    />
                </Field>
                <Field id="duties" label={t('jobs.field.duties')} error={errors.duties}>
                    <TextArea
                        id="duties"
                        value={data.duties}
                        maxLength={5000}
                        placeholder={t('jobs.field.duties_placeholder')}
                        onChange={(e) => form.setData('duties', e.target.value)}
                    />
                </Field>
                <StepActions processing={form.processing} />
            </Card>
        </form>
    );
}

type ApplyForm = Record<
    | 'permit_payer'
    | 'visa_payer'
    | 'contract_duration'
    | 'contract_other'
    | 'worker_fees'
    | 'apply_method'
    | 'apply_url'
    | 'deadline'
    | 'additional_details',
    string
> & { costs: CostRow[] };

function ApplyStep({ code, form: job, options }: Props) {
    const { t } = useTranslation();
    const destination = options.destinations.find(
        (item) => item.code === job?.destinationCountryCode,
    );
    const allowed = new Set(destination?.allowedWorkerCosts ?? []);
    const form = useForm<ApplyForm>({
        permit_payer: job?.permitPayer ?? '',
        visa_payer: job?.visaPayer ?? '',
        contract_duration: job?.contractDuration ?? '',
        contract_other: job?.contractOther ?? '',
        worker_fees: job?.workerFees ?? '',
        costs: (job?.costs ?? []).map((cost) => ({
            category: cost.category,
            description: cost.description ?? '',
            amount: cost.amount.toString(),
            currency: cost.currency,
            payer: cost.payer,
        })),
        apply_method: job?.applyMethod ?? 'blueskills',
        apply_url: job?.applyUrl ?? '',
        deadline: job?.deadline ?? '',
        additional_details: job?.additionalDetails ?? '',
    });
    const data = form.data;
    const errors = form.errors as Errors;
    const check = () => {
        const found: Errors = {};
        requireOther(found, t('jobs.validation.other_required'), [
            [data.contract_duration, data.contract_other, 'contract_other'],
        ]);
        const itemised = data.costs.some((cost) => cost.payer === 'worker');
        const workerPaysVisa = data.visa_payer === 'worker' || data.permit_payer === 'worker';

        if (data.worker_fees === 'apply' && !itemised) {
            found.worker_fees = t('jobs.validation.fees_need_items');
        }

        if (data.worker_fees === 'none' && (itemised || workerPaysVisa)) {
            found.worker_fees = t('jobs.validation.fees_none_but_items');
        }

        if (data.apply_method === 'both' && data.apply_url.trim() === '') {
            found.apply_url = t('jobs.validation.apply_url_required');
        } else if (
            data.apply_method === 'both' &&
            !/^https?:\/\/\S+\.\S+/i.test(data.apply_url.trim())
        ) {
            found.apply_url = t('jobs.validation.apply_url_invalid');
        }

        return found;
    };
    const submit = useStepSubmit(code, 'apply')(form, check);
    const setCost = (index: number, key: string, value: string) =>
        form.setData(
            'costs',
            data.costs.map((cost, i) => (i === index ? { ...cost, [key]: value } : cost)),
        );

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <fieldset className="flex flex-col gap-3">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('jobs.field.visa_support')}
                    </legend>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {(['visa_payer', 'permit_payer'] as const).map((key) => (
                            <Field
                                key={key}
                                id={key}
                                label={t(`jobs.field.${key}`)}
                                error={errors[key]}
                            >
                                <NativeSelect
                                    id={key}
                                    value={data[key]}
                                    onChange={(e) => form.setData(key, e.target.value)}
                                >
                                    {PAYERS.map((payer) => (
                                        <option key={payer} value={payer}>
                                            {t(`truth_card.payer.${payer}`)}
                                        </option>
                                    ))}
                                    <option value="">{t('jobs.field.unknown')}</option>
                                </NativeSelect>
                            </Field>
                        ))}
                    </div>
                </fieldset>
                <OptionSelect
                    id="contract_duration"
                    option="contract_duration"
                    label={t('jobs.field.contract')}
                    value={data.contract_duration}
                    onChange={(value) => form.setData('contract_duration', value)}
                    error={errors.contract_duration}
                />
                {data.contract_duration === 'other' && (
                    <OtherInput
                        id="contract_other"
                        value={data.contract_other}
                        onChange={(value) => form.setData('contract_other', value)}
                        error={errors.contract_other}
                    />
                )}
                <OptionSelect
                    id="worker_fees"
                    option="worker_fees"
                    label={t('jobs.field.worker_fees')}
                    hint={t('jobs.field.worker_fees_hint')}
                    value={data.worker_fees}
                    onChange={(value) => form.setData('worker_fees', value)}
                    error={errors.worker_fees}
                />

                <fieldset className="flex flex-col gap-3">
                    <legend className="mb-1.5 text-sm font-medium">{t('jobs.field.costs')}</legend>
                    <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                        {t('jobs.field.costs_rule', {
                            country: destination?.name ?? '',
                            policy: destination?.workerCostPolicy ?? t('jobs.compliance.no_rules'),
                        })}
                    </p>
                    {data.costs.map((cost, index) => {
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
                                    required
                                    label={t('jobs.field.cost_category')}
                                >
                                    <NativeSelect
                                        aria-required
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
                                        placeholder={t('jobs.field.cost_description_placeholder')}
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
                                    required
                                    label={t('worker.salary.amount')}
                                >
                                    <Input
                                        placeholder={t('jobs.field.cost_amount_placeholder')}
                                        aria-required
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
                                    required
                                    label={t('worker.salary.currency')}
                                >
                                    <NativeSelect
                                        aria-required
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
                                    required
                                    label={t('jobs.field.cost_payer')}
                                >
                                    <NativeSelect
                                        aria-required
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
                                            data.costs.filter((_, i) => i !== index),
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
                                ...data.costs,
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

                <div className="grid gap-4 sm:grid-cols-2">
                    <OptionSelect
                        id="apply_method"
                        option="apply_method"
                        required
                        label={t('jobs.field.apply_method')}
                        value={data.apply_method}
                        empty={null}
                        onChange={(value) => form.setData('apply_method', value)}
                        error={errors.apply_method}
                    />
                    <Field id="deadline" label={t('jobs.field.deadline')} error={errors.deadline}>
                        <Input
                            id="deadline"
                            type="date"
                            value={data.deadline}
                            onChange={(e) => form.setData('deadline', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                </div>
                {data.apply_method === 'both' && (
                    <Field
                        id="apply_url"
                        required
                        label={t('jobs.field.apply_url')}
                        hint={t('jobs.field.apply_url_hint')}
                        error={errors.apply_url}
                    >
                        <Input
                            aria-required
                            id="apply_url"
                            type="url"
                            inputMode="url"
                            dir="ltr"
                            maxLength={2048}
                            placeholder={t('jobs.field.apply_url_placeholder')}
                            aria-describedby="apply_url-hint"
                            value={data.apply_url}
                            onChange={(e) => form.setData('apply_url', e.target.value)}
                            className="h-11 text-base"
                        />
                    </Field>
                )}
                <Field
                    id="additional_details"
                    label={t('jobs.field.additional_details')}
                    error={errors.additional_details}
                >
                    <TextArea
                        id="additional_details"
                        rows={5}
                        value={data.additional_details}
                        maxLength={10000}
                        placeholder={t('jobs.field.additional_details_placeholder')}
                        onChange={(e) => form.setData('additional_details', e.target.value)}
                    />
                </Field>
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
