import { Head, Link, router } from '@inertiajs/react';
import { Globe2 } from 'lucide-react';
import { DataTable } from '@/components/bs/data-table';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Country = App.Domain.Config.Data.CountryConfigData;
type LanguageRow = { code: string; name: string; nativeName: string; rtl: boolean; status: string };

type Props = {
    countries: Country[];
    languages: LanguageRow[];
    languageStatuses: string[];
};

/** Countries, corridor rules and languages (prototype x-config, D-093). */
export default function ConfigIndex({ countries, languages, languageStatuses }: Props) {
    const { t, formatDate } = useTranslation();

    return (
        <>
            <Head title={t('nav.configuration')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.configuration')} description={t('config.body')} />

                <section className="flex flex-col gap-3" aria-labelledby="countries-heading">
                    <h2 id="countries-heading" className="eyebrow text-muted">
                        {t('config.countries')}
                    </h2>
                    <DataTable<Country>
                        caption={t('config.countries')}
                        rows={countries}
                        rowKey={(row) => row.code}
                        columns={[
                            {
                                key: 'country',
                                header: t('config.column.country'),
                                primary: true,
                                cell: (row) => (
                                    <Link
                                        href={admin.config.countries.show(row.code)}
                                        className="font-semibold text-brand hover:underline"
                                    >
                                        {row.name}
                                        <span className="block font-mono text-xs font-normal text-muted">
                                            {row.code} · {row.currency}
                                        </span>
                                    </Link>
                                ),
                            },
                            {
                                key: 'roles',
                                header: t('config.column.roles'),
                                cell: (row) =>
                                    [
                                        row.source && t('config.source'),
                                        row.destination && t('config.destination'),
                                    ]
                                        .filter(Boolean)
                                        .join(' · ') || '—',
                            },
                            {
                                key: 'legal',
                                header: t('config.column.legal'),
                                cell: (row) =>
                                    !row.destination ? (
                                        <span className="text-muted">—</span>
                                    ) : row.legalReviewedAt ? (
                                        <StatusChip
                                            tone="ok"
                                            label={t('config.legal_reviewed_on', {
                                                date: formatDate(row.legalReviewedAt),
                                            })}
                                        />
                                    ) : (
                                        <StatusChip tone="warn" label={t('config.legal_pending')} />
                                    ),
                            },
                        ]}
                    />
                </section>

                <section className="flex flex-col gap-3" aria-labelledby="languages-heading">
                    <h2
                        id="languages-heading"
                        className="inline-flex items-center gap-1.5 eyebrow text-muted"
                    >
                        <Globe2 aria-hidden className="size-4" />
                        {t('config.languages')}
                    </h2>
                    <ul className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {languages.map((language) => (
                            <li
                                key={language.code}
                                className="flex flex-col gap-2 rounded-xl border border-line bg-surface p-4"
                            >
                                <p className="font-semibold">
                                    <span lang={language.code} dir={language.rtl ? 'rtl' : 'ltr'}>
                                        {language.nativeName}
                                    </span>{' '}
                                    <span className="text-sm font-normal text-muted">
                                        {language.name}
                                    </span>
                                </p>
                                <Label htmlFor={`language-${language.code}`} className="sr-only">
                                    {t('config.language_status', { language: language.name })}
                                </Label>
                                <NativeSelect
                                    id={`language-${language.code}`}
                                    value={language.status}
                                    onChange={(event) =>
                                        router.put(
                                            admin.config.languages.update.url(language.code),
                                            { status: event.target.value },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {languageStatuses.map((status) => (
                                        <option key={status} value={status}>
                                            {t(`config.language_state.${status}`)}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </>
    );
}
