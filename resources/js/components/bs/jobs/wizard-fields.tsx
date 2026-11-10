import type { ReactNode, TextareaHTMLAttributes } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** Option lists of the Post a Job wizard, in the job posting prototype's order (D-117). */
export const JOB_OPTIONS = {
    employment_type: ['full_time', 'part_time', 'seasonal', 'fixed_term'],
    salary_basis: ['net', 'gross'],
    working_hours: [
        'day_6_8',
        'day_8_10',
        'day_10_12',
        'month_160_200',
        'month_200_270',
        'variable',
        'other',
    ],
    working_days: ['days_5', 'days_5_6', 'days_6', 'rotating', 'other'],
    shift_pattern: ['day', 'night', 'rotating', 'flexible', 'other'],
    experience_level: ['none', 'under_1', '1_3', '3_5', '5_10', '10_plus', 'other'],
    education_level: ['none', 'primary', 'secondary', 'diploma', 'degree', 'other'],
    age_range: ['18_25', '18_35', '18_45', '18_50', '18_60', 'other'],
    contract_duration: [
        '3_months',
        '6_months',
        '9_months',
        '12_months',
        '12_24_months',
        '12_36_months',
        '2_years',
        'permanent',
        'other',
    ],
    worker_fees: ['none', 'apply'],
    apply_method: ['blueskills', 'both'],
} as const;

export type JobOptionKey = keyof typeof JOB_OPTIONS;

export function WizardCard({ children }: { children: ReactNode }) {
    return (
        <div className="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5">
            {children}
        </div>
    );
}

export function Field({
    id,
    label,
    error,
    hint,
    required = false,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    /** Shows the asterisk (D-118); give the control `aria-required` too. */
    required?: boolean;
    children: ReactNode;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <Label htmlFor={id} required={required}>
                {label}
            </Label>
            {children}
            {hint && (
                <p id={`${id}-hint`} className="text-xs text-muted">
                    {hint}
                </p>
            )}
            <InputError message={error} />
        </div>
    );
}

/**
 * A select over one of the wizard's option lists. The empty choice is "Unknown" (or a prompt):
 * the job then shows "Not disclosed", never a guess (product rule 4).
 */
export function OptionSelect({
    id,
    option,
    label,
    value,
    onChange,
    error,
    hint,
    empty,
    required = false,
}: {
    id: string;
    option: JobOptionKey;
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
    /** Label of the empty choice; null hides it (the field always has a value). */
    empty?: string | null;
    required?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <Field id={id} label={label} error={error} hint={hint} required={required}>
            <NativeSelect
                id={id}
                value={value}
                aria-required={required || undefined}
                aria-describedby={hint ? `${id}-hint` : undefined}
                onChange={(event) => onChange(event.target.value)}
            >
                {empty !== null && <option value="">{empty ?? t('jobs.field.unknown')}</option>}
                {JOB_OPTIONS[option].map((item) => (
                    <option key={item} value={item}>
                        {t(`jobs.option.${option}.${item}`)}
                    </option>
                ))}
            </NativeSelect>
        </Field>
    );
}

/** The poster's own words, shown only after "Other" is chosen. */
export function OtherInput({
    id,
    value,
    onChange,
    error,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    const { t } = useTranslation();

    return (
        // Shown only after "Other" is chosen, and then required (`required_if:…,other`).
        <Field id={id} label={t('jobs.field.other_details')} error={error} required>
            <Input
                id={id}
                aria-required
                value={value}
                maxLength={120}
                placeholder={t('jobs.field.other_placeholder')}
                onChange={(event) => onChange(event.target.value)}
                className="h-11 text-base"
            />
        </Field>
    );
}

export function TextArea({ className, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return (
        <textarea
            rows={4}
            {...props}
            className={cn(
                'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                className,
            )}
        />
    );
}
