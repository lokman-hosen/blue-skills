import { router } from '@inertiajs/react';
import { Check, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useId, useState } from 'react';
import VoiceController from '@/actions/App/Http/Controllers/Worker/VoiceController';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import type { OptionLookup } from '@/lib/passport';
import { cn } from '@/lib/utils';

type Fields = App.Domain.Worker.Data.VoiceFieldsData;
type Question = App.Domain.Worker.Enums.VoiceQuestion;

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

type Props = {
    question: Question;
    initial: Fields | null;
    answerId: string | null;
    options: App.Domain.Worker.Data.ProfileOptionsData;
    lookup: OptionLookup;
    /** The worker's trade, used to list typical skills first. */
    occupationSlug: string | null;
    onCancel?: () => void;
};

/**
 * Structured fields for one Voice-to-CV question. Saving here is the worker's confirmation:
 * nothing reaches the Skill Passport without it (product rule 2).
 */
export function VoiceFieldsEditor({
    question,
    initial,
    answerId,
    options,
    lookup,
    occupationSlug,
    onCancel,
}: Props) {
    const { t } = useTranslation();
    const id = useId();
    const [occupation, setOccupation] = useState(initial?.occupation ?? occupationSlug ?? '');
    const [specialization, setSpecialization] = useState(initial?.specialization ?? '');
    const [years, setYears] = useState(initial?.yearsExperience?.toString() ?? '');
    const [countries, setCountries] = useState(
        initial?.countries?.length
            ? initial.countries.map((row) => ({
                  code: row.code,
                  years: row.years?.toString() ?? '',
              }))
            : [{ code: '', years: '' }],
    );
    const [skills, setSkills] = useState<string[]>(initial?.skills ?? []);
    const [passportStatus, setPassportStatus] = useState(initial?.passportStatus ?? '');
    const [validUntil, setValidUntil] = useState(initial?.passportValidUntilYear?.toString() ?? '');
    const [availability, setAvailability] = useState(initial?.availability ?? '');
    const [destinations, setDestinations] = useState<string[]>(initial?.destinations ?? []);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const payload = (): Record<string, unknown> => {
        switch (question) {
            case 'occupation':
                return { occupation, specialization: specialization || null };
            case 'experience':
                return { years_experience: years };
            case 'countries':
                return { countries: countries.filter((row) => row.code !== '') };
            case 'skills':
                return { skills };
            case 'passport':
                return {
                    passport_status: passportStatus,
                    passport_valid_until_year: validUntil || null,
                };
            case 'availability':
                return { availability };
            case 'destinations':
                return { destinations };
        }
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.post(
            VoiceController.confirm.url(question),
            { answer_id: answerId, ...payload() } as never,
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (bag) => setErrors(bag),
            },
        );
    };

    const firstError = (prefix: string) =>
        Object.entries(errors).find(([key]) => key === prefix || key.startsWith(`${prefix}.`))?.[1];

    const toggle = (list: string[], value: string) =>
        list.includes(value) ? list.filter((item) => item !== value) : [...list, value];

    const occupationOption = lookup.occupation(occupation || null);

    return (
        <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
            {question === 'occupation' && (
                <>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`${id}-occupation`}>{t('voice.label.occupation')}</Label>
                        <NativeSelect
                            id={`${id}-occupation`}
                            value={occupation}
                            onChange={(event) => {
                                setOccupation(event.target.value);
                                setSpecialization('');
                            }}
                            aria-invalid={!!errors.occupation}
                        >
                            <option value="" disabled>
                                {t('voice.choose_trade')}
                            </option>
                            {options.occupations.map((item) => (
                                <option key={item.slug} value={item.slug}>
                                    {item.name} · {item.sector}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={errors.occupation} />
                    </div>
                    {occupationOption && occupationOption.specializations.length > 0 && (
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor={`${id}-specialization`}>
                                {t('voice.label.specialization')}
                            </Label>
                            <NativeSelect
                                id={`${id}-specialization`}
                                value={specialization}
                                onChange={(event) => setSpecialization(event.target.value)}
                            >
                                <option value="">{t('common.optional')}</option>
                                {occupationOption.specializations.map((item) => (
                                    <option key={item.slug} value={item.slug}>
                                        {item.name}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.specialization} />
                        </div>
                    )}
                </>
            )}

            {question === 'experience' && (
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor={`${id}-years`}>{t('voice.label.experience')}</Label>
                    <Input
                        id={`${id}-years`}
                        type="number"
                        inputMode="numeric"
                        min={0}
                        max={60}
                        value={years}
                        onChange={(event) => setYears(event.target.value)}
                        className="h-11 max-w-40 text-base"
                        aria-invalid={!!errors.years_experience}
                    />
                    <InputError message={errors.years_experience} />
                </div>
            )}

            {question === 'countries' && (
                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('voice.label.countries')}
                    </legend>
                    {countries.map((row, index) => (
                        <div key={index} className="flex items-end gap-2">
                            <div className="flex min-w-0 flex-1 flex-col gap-1">
                                <Label htmlFor={`${id}-country-${index}`} className="text-xs">
                                    {t('voice.country')}
                                </Label>
                                <NativeSelect
                                    id={`${id}-country-${index}`}
                                    value={row.code}
                                    onChange={(event) =>
                                        setCountries(
                                            countries.map((item, i) =>
                                                i === index
                                                    ? { ...item, code: event.target.value }
                                                    : item,
                                            ),
                                        )
                                    }
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
                            </div>
                            <div className="flex w-24 flex-col gap-1">
                                <Label htmlFor={`${id}-country-years-${index}`} className="text-xs">
                                    {t('voice.years')}
                                </Label>
                                <Input
                                    id={`${id}-country-years-${index}`}
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    max={60}
                                    value={row.years}
                                    onChange={(event) =>
                                        setCountries(
                                            countries.map((item, i) =>
                                                i === index
                                                    ? { ...item, years: event.target.value }
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
                                disabled={countries.length === 1}
                                onClick={() =>
                                    setCountries(countries.filter((_, i) => i !== index))
                                }
                                aria-label={t('voice.remove_country')}
                            >
                                <Trash2 aria-hidden className="size-4" />
                            </Button>
                        </div>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        className="min-h-11 self-start"
                        onClick={() => setCountries([...countries, { code: '', years: '' }])}
                    >
                        <Plus aria-hidden className="size-4" />
                        {t('voice.add_country')}
                    </Button>
                    <InputError message={firstError('countries')} />
                </fieldset>
            )}

            {question === 'skills' && (
                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1.5 text-sm font-medium">
                        {t('voice.label.skills')}
                    </legend>
                    <ChipGroup
                        items={lookup.skillsFor(occupationSlug).map((skill) => ({
                            value: skill.slug,
                            label: skill.name,
                        }))}
                        selected={skills}
                        onToggle={(value) => setSkills(toggle(skills, value))}
                    />
                    <InputError message={firstError('skills')} />
                </fieldset>
            )}

            {question === 'passport' && (
                <>
                    <RadioCards
                        legend={t('voice.label.passport')}
                        name={`${id}-passport`}
                        items={PASSPORT_STATUSES.map((status) => ({
                            value: status,
                            label: t(`passport.status.${status}`),
                        }))}
                        value={passportStatus}
                        onChange={setPassportStatus}
                        error={errors.passport_status}
                    />
                    {(passportStatus === 'ready' || passportStatus === 'expired') && (
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor={`${id}-valid`}>{t('voice.label.valid_until')}</Label>
                            <Input
                                id={`${id}-valid`}
                                type="number"
                                inputMode="numeric"
                                min={2000}
                                max={2100}
                                value={validUntil}
                                onChange={(event) => setValidUntil(event.target.value)}
                                className="h-11 max-w-40 text-base"
                                aria-describedby={`${id}-valid-hint`}
                            />
                            <p id={`${id}-valid-hint`} className="text-xs text-muted">
                                {t('voice.valid_until_hint')}
                            </p>
                            <InputError message={errors.passport_valid_until_year} />
                        </div>
                    )}
                </>
            )}

            {question === 'availability' && (
                <RadioCards
                    legend={t('voice.label.availability')}
                    name={`${id}-availability`}
                    items={AVAILABILITY.map((value) => ({
                        value,
                        label: t(`availability.${value}`),
                    }))}
                    value={availability}
                    onChange={setAvailability}
                    error={errors.availability}
                />
            )}

            {question === 'destinations' && (
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
                        selected={destinations}
                        onToggle={(value) => setDestinations(toggle(destinations, value))}
                        ordered
                    />
                    <InputError message={firstError('destinations')} />
                </fieldset>
            )}

            {errors.answer_id && <InputError message={errors.answer_id} />}

            <div className="flex flex-col gap-2 sm:flex-row-reverse sm:justify-start">
                <Button
                    type="submit"
                    className="min-h-12 text-base sm:min-w-48"
                    disabled={processing}
                >
                    {processing ? <Spinner /> : <Check aria-hidden className="size-4" />}
                    {t('voice.save_answer')}
                </Button>
                {onCancel && (
                    <Button type="button" variant="outline" className="min-h-12" onClick={onCancel}>
                        {t('common.cancel')}
                    </Button>
                )}
            </div>
        </form>
    );
}

export function ChipGroup({
    items,
    selected,
    onToggle,
    ordered = false,
}: {
    items: { value: string; label: string }[];
    selected: string[];
    onToggle: (value: string) => void;
    /** Show the order of selection (first choice, second choice…). */
    ordered?: boolean;
}) {
    const { formatNumber } = useTranslation();

    return (
        <ul className="flex flex-wrap gap-2">
            {items.map((item) => {
                const position = selected.indexOf(item.value);
                const active = position !== -1;

                return (
                    <li key={item.value}>
                        <button
                            type="button"
                            aria-pressed={active}
                            onClick={() => onToggle(item.value)}
                            className={cn(
                                'inline-flex min-h-11 items-center gap-1.5 rounded-full px-3.5 text-[13px] font-semibold transition',
                                active
                                    ? 'bg-brand text-on-brand'
                                    : 'border border-line text-muted hover:border-brand/60 hover:text-ink',
                            )}
                        >
                            {active &&
                                (ordered ? (
                                    <span className="grid size-5 place-items-center rounded-full bg-on-brand/20 text-[11px]">
                                        {formatNumber(position + 1)}
                                    </span>
                                ) : (
                                    <Check aria-hidden className="size-3.5" />
                                ))}
                            {item.label}
                        </button>
                    </li>
                );
            })}
        </ul>
    );
}

function RadioCards({
    legend,
    name,
    items,
    value,
    onChange,
    error,
}: {
    legend: string;
    name: string;
    items: { value: string; label: string }[];
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <fieldset className="flex flex-col gap-2">
            <legend className="mb-1.5 text-sm font-medium">{legend}</legend>
            <div className="grid gap-2 sm:grid-cols-2">
                {items.map((item) => (
                    <label
                        key={item.value}
                        className={cn(
                            'flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border px-3.5 text-sm font-semibold focus-within:ring-[3px] focus-within:ring-ring/50',
                            value === item.value
                                ? 'border-brand bg-brand-soft text-brand'
                                : 'border-line hover:border-brand/60',
                        )}
                    >
                        <input
                            type="radio"
                            name={name}
                            value={item.value}
                            checked={value === item.value}
                            onChange={() => onChange(item.value)}
                            className="size-4 accent-[var(--color-brand)]"
                        />
                        {item.label}
                    </label>
                ))}
            </div>
            <InputError message={error} />
        </fieldset>
    );
}
