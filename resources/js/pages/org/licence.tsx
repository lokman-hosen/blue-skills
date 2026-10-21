import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    licence: {
        number: string | null;
        expiresOn: string | null;
        verified: boolean;
        valid: boolean;
        countries: string[];
    };
    countries: App.Domain.Config.Data.CountryOptionData[];
    canEdit: boolean;
};

/** The agency's recruiting licence and the destinations it covers (product rule 10). */
export default function Licence({ licence, countries, canEdit }: Props) {
    const { t, formatDate } = useTranslation();
    const form = useForm<{ countries: string[] }>({ countries: licence.countries });

    const toggle = (code: string, on: boolean) =>
        form.setData(
            'countries',
            on ? [...form.data.countries, code] : form.data.countries.filter((c) => c !== code),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(org.licence.update.url(), { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('licence.title')} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader title={t('licence.title')} description={t('licence.body')} />
                <section className="grid gap-3 rounded-2xl border border-line bg-surface p-5 sm:grid-cols-3">
                    <div>
                        <p className="text-xs text-muted">{t('licence.number')}</p>
                        <p className="font-mono font-bold" dir="ltr">
                            {licence.number ?? '—'}
                        </p>
                    </div>
                    <div>
                        <p className="text-xs text-muted">{t('licence.expires')}</p>
                        <p className="font-semibold">
                            {licence.expiresOn ? formatDate(licence.expiresOn) : '—'}
                        </p>
                    </div>
                    <div className="flex items-start">
                        <StatusChip
                            tone={licence.valid ? 'ok' : 'bad'}
                            label={
                                licence.valid
                                    ? t('licence.valid')
                                    : licence.verified
                                      ? t('licence.expired')
                                      : t('licence.not_verified')
                            }
                        />
                    </div>
                </section>
                <form
                    onSubmit={submit}
                    noValidate
                    className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5"
                >
                    <fieldset className="flex flex-col gap-1">
                        <legend className="mb-1 font-semibold">{t('licence.countries')}</legend>
                        <p className="mb-2 text-xs text-muted">{t('licence.countries_hint')}</p>
                        <div className="grid gap-x-4 sm:grid-cols-2">
                            {countries.map((country) => (
                                <label
                                    key={country.code}
                                    className="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        disabled={!canEdit}
                                        checked={form.data.countries.includes(country.code)}
                                        onChange={(event) =>
                                            toggle(country.code, event.target.checked)
                                        }
                                        className="size-5 accent-[var(--color-brand)]"
                                    />
                                    {country.name}
                                </label>
                            ))}
                        </div>
                    </fieldset>
                    <InputError message={form.errors.countries} />
                    {canEdit && (
                        <Button
                            type="submit"
                            className="min-h-11 self-start"
                            disabled={form.processing || !form.isDirty}
                        >
                            {form.processing && <Spinner />}
                            {t('common.save')}
                        </Button>
                    )}
                </form>
            </div>
        </>
    );
}
