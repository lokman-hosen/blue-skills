import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useMemo, useState } from 'react';
import PassportController from '@/actions/App/Http/Controllers/Worker/PassportController';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import { EvidenceUploadSection } from '@/components/bs/evidence-upload-section';
import { FileUpload } from '@/components/bs/file-upload';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { ChipGroup } from '@/components/bs/voice-fields-editor';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { optionLookup } from '@/lib/passport';
import worker from '@/routes/worker';

type Passport = App.Domain.Worker.Data.WorkerPassportData;
type Options = App.Domain.Worker.Data.ProfileOptionsData;

type Props = {
    section: 'about' | 'experience' | 'skills' | 'languages' | 'preferences' | 'photo' | 'evidence';
    passport: Passport;
    options: Options;
    languages: App.Domain.Config.Data.LocaleOptionData[];
    currencies: string[];
    photoMaxMb: number;
    evidenceTypes: App.Domain.Verification.Enums.EvidenceType[];
    evidenceMaxMb: number;
};

/** Edit one section of the Skill Passport. Only the worker can change it (product rule 1). */
export default function PassportEdit(props: Props) {
    const { t } = useTranslation();
    const title = t(`worker.section.${props.section}`);

    return (
        <>
            <Head title={title} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader
                    title={title}
                    back={{ href: worker.passport.show(), label: t('passport.title') }}
                />
                {props.section === 'about' && <AboutForm {...props} />}
                {props.section === 'experience' && <ExperienceList {...props} />}
                {props.section === 'skills' && <SkillsForm {...props} />}
                {props.section === 'languages' && <LanguagesForm {...props} />}
                {props.section === 'preferences' && <PreferencesForm {...props} />}
                {props.section === 'photo' && <PhotoForm {...props} />}
                {props.section === 'evidence' && (
                    <EvidenceUploadSection
                        evidence={props.passport.evidence}
                        types={props.evidenceTypes}
                        maxSizeMb={props.evidenceMaxMb}
                    />
                )}
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

function SaveButton({ processing, label }: { processing: boolean; label?: string }) {
    const { t } = useTranslation();

    return (
        <Button type="submit" className="min-h-12 self-start sm:min-w-40" disabled={processing}>
            {processing && <Spinner />}
            {label ?? t('common.save')}
        </Button>
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
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

function AboutForm({ passport, options }: Props) {
    const { t } = useTranslation();
    const occupationIds = useMemo(
        () => new Map(options.occupations.map((item) => [item.id, item])),
        [options.occupations],
    );
    const form = useForm({
        headline: passport.headline ?? '',
        occupation_id: passport.occupation?.id ?? '',
        specialization_id: passport.specialization?.id ?? '',
        years_experience: passport.yearsExperience?.toString() ?? '',
        current_country_code: passport.currentCountry?.code ?? '',
    });
    const occupation = occupationIds.get(form.data.occupation_id);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(PassportController.updateAbout.url());
    };

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <Field
                    id="occupation_id"
                    label={t('voice.label.occupation')}
                    error={form.errors.occupation_id}
                >
                    <NativeSelect
                        id="occupation_id"
                        value={form.data.occupation_id}
                        onChange={(event) =>
                            form.setData((data) => ({
                                ...data,
                                occupation_id: event.target.value,
                                specialization_id: '',
                            }))
                        }
                    >
                        <option value="" disabled>
                            {t('voice.choose_trade')}
                        </option>
                        {options.occupations.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name} · {item.sector}
                            </option>
                        ))}
                    </NativeSelect>
                </Field>
                {occupation && occupation.specializations.length > 0 && (
                    <Field
                        id="specialization_id"
                        label={t('voice.label.specialization')}
                        error={form.errors.specialization_id}
                    >
                        <NativeSelect
                            id="specialization_id"
                            value={form.data.specialization_id}
                            onChange={(event) =>
                                form.setData('specialization_id', event.target.value)
                            }
                        >
                            <option value="">{t('common.optional')}</option>
                            {occupation.specializations.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                )}
                <Field
                    id="headline"
                    label={t('worker.field.headline')}
                    hint={t('worker.field.headline_hint')}
                    error={form.errors.headline}
                >
                    <Input
                        id="headline"
                        value={form.data.headline}
                        maxLength={120}
                        onChange={(event) => form.setData('headline', event.target.value)}
                        className="h-11 text-base"
                    />
                </Field>
                <Field
                    id="years_experience"
                    label={t('worker.basics.years')}
                    error={form.errors.years_experience}
                >
                    <Input
                        id="years_experience"
                        type="number"
                        inputMode="numeric"
                        min={0}
                        max={60}
                        value={form.data.years_experience}
                        onChange={(event) => form.setData('years_experience', event.target.value)}
                        className="h-11 max-w-40 text-base"
                    />
                </Field>
                <Field
                    id="current_country_code"
                    label={t('worker.field.current_country')}
                    error={form.errors.current_country_code}
                >
                    <NativeSelect
                        id="current_country_code"
                        value={form.data.current_country_code}
                        onChange={(event) =>
                            form.setData('current_country_code', event.target.value)
                        }
                    >
                        {options.countries.map((country) => (
                            <option key={country.code} value={country.code}>
                                {country.name}
                            </option>
                        ))}
                    </NativeSelect>
                </Field>
                <SaveButton processing={form.processing} />
            </Card>
        </form>
    );
}

type ExperienceFormData = {
    country_code: string;
    role: string;
    employer_name: string;
    by: 'years' | 'dates';
    years: string;
    started_on: string;
    ended_on: string;
    description: string;
};

function ExperienceList({ passport, options }: Props) {
    const { t, formatNumber, formatDate } = useTranslation();
    const [editing, setEditing] = useState<string | 'new' | null>(
        passport.experiences.length === 0 ? 'new' : null,
    );

    return (
        <div className="flex flex-col gap-3">
            {passport.experiences.map((experience) =>
                editing === experience.id ? (
                    <ExperienceForm
                        key={experience.id}
                        experience={experience}
                        options={options}
                        onDone={() => setEditing(null)}
                    />
                ) : (
                    <div
                        key={experience.id}
                        className="flex items-start gap-3 rounded-xl border border-line bg-surface p-4"
                    >
                        <div className="min-w-0 flex-1">
                            <p className="font-semibold">{experience.role}</p>
                            <p className="text-sm text-muted">
                                {[experience.employerName, experience.countryName]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                            <p className="text-xs text-muted">
                                {experience.startedOn
                                    ? `${formatDate(experience.startedOn, { year: 'numeric', month: 'short' })} – ${
                                          experience.endedOn
                                              ? formatDate(experience.endedOn, {
                                                    year: 'numeric',
                                                    month: 'short',
                                                })
                                              : t('worker.experience.present')
                                      }`
                                    : experience.years !== null
                                      ? t('passport.years', {
                                            count: formatNumber(experience.years),
                                        })
                                      : null}
                            </p>
                        </div>
                        <Button
                            variant="ghost"
                            className="size-11"
                            onClick={() => setEditing(experience.id)}
                            aria-label={`${t('common.edit')}: ${experience.role}`}
                        >
                            <Pencil aria-hidden className="size-4" />
                        </Button>
                        <ConfirmDialog
                            trigger={
                                <Button
                                    variant="ghost"
                                    className="size-11 text-bad"
                                    aria-label={`${t('common.delete')}: ${experience.role}`}
                                >
                                    <Trash2 aria-hidden className="size-4" />
                                </Button>
                            }
                            title={t('worker.experience.delete_title')}
                            description={t('worker.experience.delete_body', {
                                role: experience.role,
                            })}
                            confirmLabel={t('common.delete')}
                            destructive
                            onConfirm={() =>
                                router.delete(
                                    PassportController.destroyExperience.url(experience.id),
                                    { preserveScroll: true },
                                )
                            }
                        />
                    </div>
                ),
            )}
            {editing === 'new' ? (
                <ExperienceForm options={options} onDone={() => setEditing(null)} />
            ) : (
                <Button
                    variant="outline"
                    className="min-h-11 self-start"
                    onClick={() => setEditing('new')}
                >
                    <Plus aria-hidden className="size-4" />
                    {t('worker.experience.add')}
                </Button>
            )}
        </div>
    );
}

function ExperienceForm({
    experience,
    options,
    onDone,
}: {
    experience?: App.Domain.Worker.Data.WorkerExperienceData;
    options: Options;
    onDone: () => void;
}) {
    const { t } = useTranslation();
    const form = useForm<ExperienceFormData>({
        country_code: experience?.countryCode ?? '',
        role: experience?.role ?? '',
        employer_name: experience?.employerName ?? '',
        by: experience?.startedOn ? 'dates' : 'years',
        years: experience?.startedOn ? '' : (experience?.years?.toString() ?? ''),
        started_on: experience?.startedOn ?? '',
        ended_on: experience?.endedOn ?? '',
        description: experience?.description ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform(({ by, ...data }) => ({
            ...data,
            years: by === 'years' ? data.years : '',
            started_on: by === 'dates' ? data.started_on : '',
            ended_on: by === 'dates' ? data.ended_on : '',
        }));
        const options = { preserveScroll: true, onSuccess: onDone };

        if (experience) {
            form.put(PassportController.updateExperience.url(experience.id), options);
        } else {
            form.post(PassportController.storeExperience.url(), options);
        }
    };
    const id = experience?.id ?? 'new';

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <Field
                    id={`${id}-role`}
                    label={t('worker.experience.role')}
                    error={form.errors.role}
                >
                    <Input
                        id={`${id}-role`}
                        value={form.data.role}
                        onChange={(event) => form.setData('role', event.target.value)}
                        className="h-11 text-base"
                    />
                </Field>
                <Field
                    id={`${id}-country`}
                    label={t('voice.country')}
                    error={form.errors.country_code}
                >
                    <NativeSelect
                        id={`${id}-country`}
                        value={form.data.country_code}
                        onChange={(event) => form.setData('country_code', event.target.value)}
                    >
                        <option value="" disabled>
                            {t('voice.choose_country')}
                        </option>
                        {options.countries.map((country) => (
                            <option key={country.code} value={country.code}>
                                {country.name}
                            </option>
                        ))}
                    </NativeSelect>
                </Field>
                <Field
                    id={`${id}-employer`}
                    label={`${t('worker.experience.employer')} (${t('common.optional')})`}
                    error={form.errors.employer_name}
                >
                    <Input
                        id={`${id}-employer`}
                        value={form.data.employer_name}
                        onChange={(event) => form.setData('employer_name', event.target.value)}
                        className="h-11 text-base"
                    />
                </Field>
                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('worker.experience.how_long')}
                    </legend>
                    <div className="flex flex-wrap gap-2">
                        {(['years', 'dates'] as const).map((by) => (
                            <label
                                key={by}
                                className="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-line px-3 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-soft"
                            >
                                <input
                                    type="radio"
                                    name={`${id}-by`}
                                    checked={form.data.by === by}
                                    onChange={() => form.setData('by', by)}
                                    className="size-4 accent-[var(--color-brand)]"
                                />
                                {t(`worker.experience.by_${by}`)}
                            </label>
                        ))}
                    </div>
                </fieldset>
                {form.data.by === 'years' ? (
                    <Field id={`${id}-years`} label={t('voice.years')} error={form.errors.years}>
                        <Input
                            id={`${id}-years`}
                            type="number"
                            inputMode="numeric"
                            min={0}
                            max={60}
                            value={form.data.years}
                            onChange={(event) => form.setData('years', event.target.value)}
                            className="h-11 max-w-40 text-base"
                        />
                    </Field>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field
                            id={`${id}-start`}
                            label={t('worker.experience.started')}
                            error={form.errors.started_on}
                        >
                            <Input
                                id={`${id}-start`}
                                type="date"
                                value={form.data.started_on}
                                onChange={(event) => form.setData('started_on', event.target.value)}
                                className="h-11 text-base"
                            />
                        </Field>
                        <Field
                            id={`${id}-end`}
                            label={t('worker.experience.ended')}
                            hint={t('worker.experience.ended_hint')}
                            error={form.errors.ended_on}
                        >
                            <Input
                                id={`${id}-end`}
                                type="date"
                                value={form.data.ended_on}
                                onChange={(event) => form.setData('ended_on', event.target.value)}
                                className="h-11 text-base"
                            />
                        </Field>
                    </div>
                )}
                <Field
                    id={`${id}-description`}
                    label={`${t('worker.experience.description')} (${t('common.optional')})`}
                    error={form.errors.description}
                >
                    <textarea
                        id={`${id}-description`}
                        value={form.data.description}
                        maxLength={1000}
                        rows={3}
                        onChange={(event) => form.setData('description', event.target.value)}
                        className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                </Field>
                <div className="flex flex-wrap gap-2">
                    <SaveButton processing={form.processing} />
                    <Button type="button" variant="outline" className="min-h-12" onClick={onDone}>
                        {t('common.cancel')}
                    </Button>
                </div>
            </Card>
        </form>
    );
}

function SkillsForm({ passport, options }: Props) {
    const { t } = useTranslation();
    const lookup = useMemo(() => optionLookup(options), [options]);
    const attested = passport.skills.filter((skill) => skill.source === 'institute');
    const form = useForm({
        skill_ids: passport.skills
            .filter((skill) => skill.source !== 'institute')
            .map((skill) => skill.id),
    });
    const skillIds = useMemo(
        () => new Map(options.skills.map((skill) => [skill.slug, skill.id])),
        [options.skills],
    );
    const choices = lookup
        .skillsFor(passport.occupation?.slug ?? null)
        .filter((skill) => !attested.some((item) => item.slug === skill.slug));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(PassportController.updateSkills.url());
    };

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <p className="text-sm text-muted">{t('worker.skills.hint')}</p>
                <ChipGroup
                    items={choices.map((skill) => ({
                        value: skillIds.get(skill.slug) ?? skill.slug,
                        label: skill.name,
                    }))}
                    selected={form.data.skill_ids}
                    onToggle={(value) =>
                        form.setData(
                            'skill_ids',
                            form.data.skill_ids.includes(value)
                                ? form.data.skill_ids.filter((item) => item !== value)
                                : [...form.data.skill_ids, value],
                        )
                    }
                />
                <InputError message={form.errors.skill_ids} />
                {attested.length > 0 && (
                    <p className="text-xs text-muted">
                        {t('worker.skills.attested_note', {
                            skills: attested.map((skill) => skill.name).join(', '),
                        })}
                    </p>
                )}
                <SaveButton processing={form.processing} />
            </Card>
        </form>
    );
}

const PROFICIENCIES: App.Domain.Worker.Enums.LanguageProficiency[] = [
    'basic',
    'conversational',
    'fluent',
    'native',
];

function LanguagesForm({ passport, languages }: Props) {
    const { t } = useTranslation();
    const form = useForm({
        languages: passport.languages.map((language) => ({
            code: language.code,
            proficiency: language.proficiency as string,
        })),
    });
    const update = (index: number, key: 'code' | 'proficiency', value: string) =>
        form.setData(
            'languages',
            form.data.languages.map((row, i) => (i === index ? { ...row, [key]: value } : row)),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(PassportController.updateLanguages.url());
    };
    const firstError = Object.entries(form.errors).find(([key]) =>
        key.startsWith('languages'),
    )?.[1];

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                {form.data.languages.map((row, index) => (
                    <div key={index} className="flex items-end gap-2">
                        <div className="flex min-w-0 flex-1 flex-col gap-1">
                            <Label htmlFor={`language-${index}`} className="text-xs">
                                {t('worker.languages.language')}
                            </Label>
                            <NativeSelect
                                id={`language-${index}`}
                                value={row.code}
                                onChange={(event) => update(index, 'code', event.target.value)}
                            >
                                <option value="" disabled>
                                    {t('worker.languages.choose')}
                                </option>
                                {languages.map((language) => (
                                    <option key={language.code} value={language.code}>
                                        {language.nativeName} · {language.englishName}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="flex min-w-0 flex-1 flex-col gap-1">
                            <Label htmlFor={`proficiency-${index}`} className="text-xs">
                                {t('worker.languages.level')}
                            </Label>
                            <NativeSelect
                                id={`proficiency-${index}`}
                                value={row.proficiency}
                                onChange={(event) =>
                                    update(index, 'proficiency', event.target.value)
                                }
                            >
                                {PROFICIENCIES.map((level) => (
                                    <option key={level} value={level}>
                                        {t(`proficiency.${level}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            className="size-11 shrink-0"
                            onClick={() =>
                                form.setData(
                                    'languages',
                                    form.data.languages.filter((_, i) => i !== index),
                                )
                            }
                            aria-label={t('worker.languages.remove')}
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
                        form.setData('languages', [
                            ...form.data.languages,
                            { code: '', proficiency: 'basic' },
                        ])
                    }
                >
                    <Plus aria-hidden className="size-4" />
                    {t('worker.languages.add')}
                </Button>
                <InputError message={firstError} />
                <SaveButton processing={form.processing} />
            </Card>
        </form>
    );
}

const PASSPORT_STATUSES: App.Domain.Worker.Enums.PassportStatus[] = [
    'ready',
    'applied',
    'expired',
    'none',
];
const AVAILABILITY: App.Domain.Worker.Enums.Availability[] = [
    'immediately',
    'within_30_days',
    'within_60_days',
    'within_90_days',
    'not_available',
];
const PERIODS: App.Domain.Jobs.Enums.SalaryPeriod[] = ['month', 'week', 'day', 'hour'];

function PreferencesForm({ passport, options, currencies }: Props) {
    const { t } = useTranslation();
    const form = useForm({
        passport_status: passport.passportStatus ?? '',
        passport_valid_until_year: passport.passportValidUntilYear?.toString() ?? '',
        availability: passport.availability ?? '',
        destinations: passport.destinations.map((country) => country.code),
        salary_amount: passport.salaryExpectation
            ? wholeUnits(passport.salaryExpectation).toString()
            : '',
        salary_currency: passport.salaryExpectation?.currency ?? 'AED',
        salary_period: passport.salaryPeriod ?? 'month',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(PassportController.updatePreferences.url());
    };
    const destinationsError = Object.entries(form.errors).find(([key]) =>
        key.startsWith('destinations'),
    )?.[1];

    return (
        <form onSubmit={submit} noValidate>
            <Card>
                <Field
                    id="passport_status"
                    label={t('voice.label.passport')}
                    error={form.errors.passport_status}
                >
                    <NativeSelect
                        id="passport_status"
                        value={form.data.passport_status}
                        onChange={(event) => form.setData('passport_status', event.target.value)}
                    >
                        <option value="" disabled>
                            {t('common.choose')}
                        </option>
                        {PASSPORT_STATUSES.map((status) => (
                            <option key={status} value={status}>
                                {t(`passport.status.${status}`)}
                            </option>
                        ))}
                    </NativeSelect>
                </Field>
                <Field
                    id="passport_valid_until_year"
                    label={`${t('voice.label.valid_until')} (${t('common.optional')})`}
                    hint={t('voice.valid_until_hint')}
                    error={form.errors.passport_valid_until_year}
                >
                    <Input
                        id="passport_valid_until_year"
                        type="number"
                        inputMode="numeric"
                        min={2000}
                        max={2100}
                        value={form.data.passport_valid_until_year}
                        onChange={(event) =>
                            form.setData('passport_valid_until_year', event.target.value)
                        }
                        className="h-11 max-w-40 text-base"
                    />
                </Field>
                <Field
                    id="availability"
                    label={t('voice.label.availability')}
                    error={form.errors.availability}
                >
                    <NativeSelect
                        id="availability"
                        value={form.data.availability}
                        onChange={(event) => form.setData('availability', event.target.value)}
                    >
                        <option value="" disabled>
                            {t('common.choose')}
                        </option>
                        {AVAILABILITY.map((value) => (
                            <option key={value} value={value}>
                                {t(`availability.${value}`)}
                            </option>
                        ))}
                    </NativeSelect>
                </Field>
                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('voice.label.destinations')}
                    </legend>
                    <p className="text-xs text-muted">{t('voice.destinations_hint')}</p>
                    <ChipGroup
                        items={options.destinations.map((country) => ({
                            value: country.code,
                            label: country.name,
                        }))}
                        selected={form.data.destinations}
                        onToggle={(code) =>
                            form.setData(
                                'destinations',
                                form.data.destinations.includes(code)
                                    ? form.data.destinations.filter((item) => item !== code)
                                    : [...form.data.destinations, code],
                            )
                        }
                        ordered
                    />
                    <InputError message={destinationsError} />
                </fieldset>
                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('worker.field.salary')} ({t('common.optional')})
                    </legend>
                    <div className="grid grid-cols-[minmax(0,1fr)_6.5rem] gap-2 sm:grid-cols-[minmax(0,1fr)_7rem_9rem]">
                        <div className="flex flex-col gap-1">
                            <Label htmlFor="salary_amount" className="text-xs">
                                {t('worker.salary.amount')}
                            </Label>
                            <Input
                                id="salary_amount"
                                type="number"
                                inputMode="numeric"
                                min={1}
                                value={form.data.salary_amount}
                                onChange={(event) =>
                                    form.setData('salary_amount', event.target.value)
                                }
                                className="h-11 text-base"
                            />
                        </div>
                        <div className="flex flex-col gap-1">
                            <Label htmlFor="salary_currency" className="text-xs">
                                {t('worker.salary.currency')}
                            </Label>
                            <NativeSelect
                                id="salary_currency"
                                value={form.data.salary_currency}
                                onChange={(event) =>
                                    form.setData('salary_currency', event.target.value)
                                }
                            >
                                {currencies.map((currency) => (
                                    <option key={currency} value={currency}>
                                        {currency}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="col-span-2 flex flex-col gap-1 sm:col-span-1">
                            <Label htmlFor="salary_period" className="text-xs">
                                {t('worker.salary.per')}
                            </Label>
                            <NativeSelect
                                id="salary_period"
                                value={form.data.salary_period}
                                onChange={(event) =>
                                    form.setData(
                                        'salary_period',
                                        event.target.value as App.Domain.Jobs.Enums.SalaryPeriod,
                                    )
                                }
                            >
                                {PERIODS.map((period) => (
                                    <option key={period} value={period}>
                                        {t(`salary_period.${period}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                    </div>
                    <InputError
                        message={
                            form.errors.salary_amount ??
                            form.errors.salary_currency ??
                            form.errors.salary_period
                        }
                    />
                </fieldset>
                <SaveButton processing={form.processing} />
            </Card>
        </form>
    );
}

/** Minor units back to whole units for editing. */
function wholeUnits(money: App.Support.Data.MoneyData): number {
    const digits =
        new Intl.NumberFormat('en', {
            style: 'currency',
            currency: money.currency,
        }).resolvedOptions().maximumFractionDigits ?? 2;

    return Math.round(money.amountMinor / 10 ** digits);
}

function PhotoForm({ passport, photoMaxMb }: Props) {
    const { t } = useTranslation();
    const [file, setFile] = useState<File | null>(null);
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);

    const upload = (event: FormEvent) => {
        event.preventDefault();

        if (!file) {
            return;
        }

        router.post(
            PassportController.updatePhoto.url(),
            { photo: file },
            {
                forceFormData: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(errors.photo),
            },
        );
    };

    return (
        <form onSubmit={upload} noValidate>
            <Card>
                {passport.photoUrl && (
                    <div className="flex items-center gap-4">
                        <img
                            src={passport.photoUrl}
                            alt={t('worker.photo.current')}
                            className="size-24 rounded-xl object-cover"
                        />
                        <ConfirmDialog
                            trigger={
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="min-h-11 text-bad"
                                >
                                    <Trash2 aria-hidden className="size-4" />
                                    {t('worker.photo.remove')}
                                </Button>
                            }
                            title={t('worker.photo.remove')}
                            description={t('worker.photo.remove_body')}
                            confirmLabel={t('worker.photo.remove')}
                            destructive
                            onConfirm={() => router.delete(PassportController.destroyPhoto.url())}
                        />
                    </div>
                )}
                <FileUpload
                    label={passport.photoUrl ? t('worker.photo.change') : t('worker.photo.add')}
                    value={file}
                    onChange={(selected) => {
                        setFile(selected);
                        setError(undefined);
                    }}
                    accept={['image/jpeg', 'image/png', 'image/webp']}
                    acceptLabel="JPG, PNG, WebP"
                    maxSizeMb={photoMaxMb}
                    hint={t('worker.photo.hint')}
                    error={error}
                    sensitive
                />
                <SaveButton processing={processing} label={t('worker.photo.upload')} />
            </Card>
        </form>
    );
}
