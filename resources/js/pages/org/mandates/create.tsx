import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FileUpload } from '@/components/bs/file-upload';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    employers: { id: string; name: string; countryCode: string }[];
    occupations: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
    countries: App.Domain.Config.Data.CountryOptionData[];
    licenceValid: boolean;
};

/** Ask an employer on BLUESKILLS to confirm that this agency recruits for it (D-074). */
export default function CreateMandate({ employers, occupations, countries, licenceValid }: Props) {
    const { t } = useTranslation();
    const form = useForm<{
        employer_id: string;
        occupation_ids: string[];
        destination_country_code: string;
        headcount: string;
        valid_from: string;
        valid_until: string;
        letter: File | null;
    }>({
        employer_id: '',
        occupation_ids: [],
        destination_country_code: countries[0]?.code ?? '',
        headcount: '',
        valid_from: '',
        valid_until: '',
        letter: null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.mandates.store.url(), { forceFormData: true });
    };

    if (!licenceValid || countries.length === 0) {
        return (
            <>
                <Head title={t('mandates.request')} />
                <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                    <PageHeader
                        title={t('mandates.request')}
                        back={{ href: org.mandates.index(), label: t('nav.mandates') }}
                    />
                    <p
                        role="alert"
                        className="rounded-xl border border-warn/40 bg-warn-soft px-4 py-3 text-sm"
                    >
                        {t('mandates.licence_warning')}{' '}
                        <Link
                            href={org.licence.edit()}
                            className="font-semibold text-brand hover:underline"
                        >
                            {t('licence.title')}
                        </Link>
                    </p>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={t('mandates.request')} />
            <form
                onSubmit={submit}
                noValidate
                className="mx-auto flex w-full max-w-2xl flex-col gap-5"
            >
                <PageHeader
                    title={t('mandates.request')}
                    description={t('mandates.request_body')}
                    back={{ href: org.mandates.index(), label: t('nav.mandates') }}
                />
                <div className="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="mandate-employer">{t('mandates.employer')}</Label>
                        <NativeSelect
                            id="mandate-employer"
                            value={form.data.employer_id}
                            onChange={(event) => form.setData('employer_id', event.target.value)}
                        >
                            <option value="">{t('common.choose')}</option>
                            {employers.map((employer) => (
                                <option key={employer.id} value={employer.id}>
                                    {employer.name} · {employer.countryCode}
                                </option>
                            ))}
                        </NativeSelect>
                        <p className="text-xs text-muted">{t('mandates.employer_hint')}</p>
                        <InputError
                            message={
                                form.errors.employer_id ??
                                (form.errors as Record<string, string>).employer
                            }
                        />
                    </div>
                    <fieldset className="flex flex-col gap-1">
                        <legend className="mb-1 text-sm font-medium">{t('mandates.trades')}</legend>
                        <div className="grid max-h-56 gap-x-4 overflow-y-auto rounded-lg border border-line p-2 sm:grid-cols-2">
                            {occupations.map((occupation) => (
                                <label
                                    key={occupation.id}
                                    className="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        checked={form.data.occupation_ids.includes(occupation.id)}
                                        onChange={(event) =>
                                            form.setData(
                                                'occupation_ids',
                                                event.target.checked
                                                    ? [...form.data.occupation_ids, occupation.id]
                                                    : form.data.occupation_ids.filter(
                                                          (id) => id !== occupation.id,
                                                      ),
                                            )
                                        }
                                        className="size-5 accent-[var(--color-brand)]"
                                    />
                                    {occupation.name}
                                </label>
                            ))}
                        </div>
                        <InputError message={form.errors.occupation_ids} />
                    </fieldset>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="mandate-destination">
                                {t('jobs.field.destination')}
                            </Label>
                            <NativeSelect
                                id="mandate-destination"
                                value={form.data.destination_country_code}
                                onChange={(event) =>
                                    form.setData('destination_country_code', event.target.value)
                                }
                            >
                                {countries.map((country) => (
                                    <option key={country.code} value={country.code}>
                                        {country.name}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={form.errors.destination_country_code} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="mandate-headcount">{t('mandates.headcount')}</Label>
                            <Input
                                id="mandate-headcount"
                                type="number"
                                inputMode="numeric"
                                min={1}
                                className="h-11"
                                value={form.data.headcount}
                                onChange={(event) => form.setData('headcount', event.target.value)}
                            />
                            <InputError message={form.errors.headcount} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="mandate-from">
                                {t('mandates.valid_from')} ({t('common.optional')})
                            </Label>
                            <Input
                                id="mandate-from"
                                type="date"
                                className="h-11"
                                value={form.data.valid_from}
                                onChange={(event) => form.setData('valid_from', event.target.value)}
                            />
                            <InputError message={form.errors.valid_from} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="mandate-until">{t('mandates.valid_until')}</Label>
                            <Input
                                id="mandate-until"
                                type="date"
                                className="h-11"
                                value={form.data.valid_until}
                                onChange={(event) =>
                                    form.setData('valid_until', event.target.value)
                                }
                            />
                            <InputError message={form.errors.valid_until} />
                        </div>
                    </div>
                    <FileUpload
                        label={t('mandates.letter')}
                        value={form.data.letter}
                        onChange={(file) => form.setData('letter', file)}
                        accept={['application/pdf', 'image/jpeg', 'image/png']}
                        acceptLabel="PDF, JPG, PNG"
                        maxSizeMb={10}
                        error={form.errors.letter}
                        sensitive
                    />
                </div>
                <Button type="submit" className="min-h-12 self-start" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    {t('mandates.send_request')}
                </Button>
            </form>
        </>
    );
}
