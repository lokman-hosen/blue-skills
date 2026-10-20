import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { Building2, BriefcaseBusiness, GraduationCap } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import OrganizationRegistrationController from '@/actions/App/Http/Controllers/Organization/OrganizationRegistrationController';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type OrganizationType = App.Domain.Organization.Enums.OrganizationType;

type Props = {
    countries: App.Domain.Config.Data.CountryOptionData[];
};

const types: { value: OrganizationType; icon: LucideIcon }[] = [
    { value: 'employer', icon: Building2 },
    { value: 'agency', icon: BriefcaseBusiness },
    { value: 'institute', icon: GraduationCap },
];

/** Organization details (D-036). The organization starts pending review. */
export default function RegisterOrganization({ countries }: Props) {
    const { t } = useTranslation();
    const [type, setType] = useState<OrganizationType>('employer');

    setLayoutProps({
        title: t('onboarding.org.title'),
        description: t('onboarding.org.body'),
        wide: true,
    });

    return (
        <>
            <Head title={t('onboarding.org.title')} />
            <p className="mb-4 text-center eyebrow text-brand">
                {t('onboarding.step', { step: 2, total: 3 })}
            </p>
            <Form
                {...OrganizationRegistrationController.store.form()}
                className="flex flex-col gap-5"
            >
                {({ errors, processing }) => (
                    <>
                        <fieldset className="flex flex-col gap-2">
                            <legend className="mb-2 text-sm font-semibold">
                                {t('onboarding.org.type')}
                            </legend>
                            <div className="grid gap-2 sm:grid-cols-3">
                                {types.map(({ value, icon: Icon }) => (
                                    <label
                                        key={value}
                                        className={cn(
                                            'flex min-h-11 cursor-pointer items-start gap-3 rounded-xl border border-line p-3 transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand',
                                            type === value && 'border-brand bg-brand-soft',
                                        )}
                                    >
                                        <input
                                            type="radio"
                                            name="type"
                                            value={value}
                                            checked={type === value}
                                            onChange={() => setType(value)}
                                            className="sr-only"
                                        />
                                        <Icon
                                            aria-hidden
                                            className="mt-0.5 size-5 shrink-0 text-brand"
                                        />
                                        <span className="flex flex-col">
                                            <span className="text-sm font-semibold">
                                                {t(`org.type.${value}`)}
                                            </span>
                                            <span className="text-xs text-muted">
                                                {t(`onboarding.org.type_hint.${value}`)}
                                            </span>
                                        </span>
                                    </label>
                                ))}
                            </div>
                            <InputError message={errors.type} />
                        </fieldset>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                id="legal_name"
                                label={t('onboarding.org.legal_name')}
                                error={errors.legal_name}
                                autoComplete="organization"
                                required
                            />
                            <Field
                                id="display_name"
                                label={t('onboarding.org.display_name')}
                                error={errors.display_name}
                                required
                            />
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="country_code">{t('onboarding.org.country')}</Label>
                                <NativeSelect
                                    id="country_code"
                                    name="country_code"
                                    required
                                    defaultValue=""
                                    aria-invalid={!!errors.country_code}
                                >
                                    <option value="" disabled>
                                        {t('auth.worker.country_placeholder')}
                                    </option>
                                    {countries.map((country) => (
                                        <option key={country.code} value={country.code}>
                                            {country.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={errors.country_code} />
                            </div>
                            <Field
                                id="city"
                                label={t('onboarding.org.city')}
                                error={errors.city}
                                autoComplete="address-level2"
                                required
                            />
                            <Field
                                id="registration_number"
                                label={t('onboarding.org.registration_number')}
                                hint={t(`onboarding.org.registration_hint.${type}`)}
                                error={errors.registration_number}
                                required
                            />
                            <Field
                                id="title"
                                label={t('onboarding.org.your_title')}
                                error={errors.title}
                                autoComplete="organization-title"
                            />
                            {type === 'agency' && (
                                <>
                                    <Field
                                        id="licence_number"
                                        label={t('onboarding.org.licence_number')}
                                        error={errors.licence_number}
                                        required
                                    />
                                    <Field
                                        id="licence_expires_on"
                                        type="date"
                                        label={t('onboarding.org.licence_expires_on')}
                                        error={errors.licence_expires_on}
                                        required
                                    />
                                </>
                            )}
                        </div>

                        <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                            {t('onboarding.org.review_note')}
                        </p>
                        <Button
                            type="submit"
                            className="min-h-12 w-full text-base"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            {t('onboarding.org.submit')}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

type FieldProps = {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    type?: string;
    required?: boolean;
    autoComplete?: string;
};

function Field({
    id,
    label,
    error,
    hint,
    type = 'text',
    required = false,
    autoComplete,
}: FieldProps) {
    const describedBy =
        [hint ? `${id}-hint` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') ||
        undefined;

    return (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                name={id}
                type={type}
                required={required}
                autoComplete={autoComplete}
                className="h-11"
                aria-invalid={!!error}
                aria-describedby={describedBy}
            />
            {hint && (
                <p id={`${id}-hint`} className="text-xs text-muted">
                    {hint}
                </p>
            )}
            <InputError id={`${id}-error`} message={error} />
        </div>
    );
}
