import { Head, router } from '@inertiajs/react';
import { ScrollText } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterSheet } from '@/components/bs/filter-sheet';
import type { Paginated } from '@/components/bs/jobs/types';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Entry = App.Domain.Trust.Data.AuditEntryData;
type Filters = {
    area: string | null;
    actor: string | null;
    subject: string | null;
    from: string | null;
    to: string | null;
};

type Props = {
    entries: Paginated<Entry>;
    filters: Filters;
    areas: string[];
};

const EMPTY: Filters = { area: null, actor: null, subject: null, from: null, to: null };

/** Every recorded change, newest first (spec §10). */
export default function AuditLog({ entries, filters, areas }: Props) {
    const { t, formatDate } = useTranslation();
    const [draft, setDraft] = useState<Filters>(filters);
    const active = Object.values(filters).filter(Boolean).length;

    const apply = (next: Filters) =>
        router.get(
            admin.audit.index.url({
                query: Object.fromEntries(
                    Object.entries(next).filter(([, value]) => value),
                ) as Record<string, string>,
            }),
            {},
            { preserveScroll: true },
        );

    const field = (key: 'actor' | 'subject' | 'from' | 'to', type = 'text') => (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={`audit-${key}`}>{t(`admin.audit.filter.${key}`)}</Label>
            <Input
                id={`audit-${key}`}
                type={type}
                className="h-11"
                value={draft[key] ?? ''}
                onChange={(event) => setDraft({ ...draft, [key]: event.target.value || null })}
            />
        </div>
    );

    return (
        <>
            <Head title={t('admin.audit.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('admin.audit.title')} description={t('admin.audit.body')} />
                <div className="grid gap-5 md:grid-cols-[16rem_minmax(0,1fr)]">
                    <FilterSheet
                        activeCount={active}
                        onApply={() => apply(draft)}
                        onClear={() => apply(EMPTY)}
                        className="self-start"
                    >
                        <div className="flex flex-col gap-4">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="audit-area">{t('admin.audit.filter.area')}</Label>
                                <NativeSelect
                                    id="audit-area"
                                    value={draft.area ?? ''}
                                    onChange={(event) =>
                                        setDraft({ ...draft, area: event.target.value || null })
                                    }
                                >
                                    <option value="">{t('admin.audit.all_areas')}</option>
                                    {areas.map((area) => (
                                        <option key={area} value={area}>
                                            {area}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </div>
                            {field('actor')}
                            {field('subject')}
                            {field('from', 'date')}
                            {field('to', 'date')}
                            <Button
                                className="hidden min-h-11 md:inline-flex"
                                onClick={() => apply(draft)}
                            >
                                {t('common.show_results')}
                            </Button>
                        </div>
                    </FilterSheet>
                    <div className="flex min-w-0 flex-col gap-3">
                        {entries.data.length === 0 ? (
                            <EmptyState
                                icon={ScrollText}
                                title={t('admin.audit.empty_title')}
                                description={t('admin.audit.empty_body')}
                            />
                        ) : (
                            <ol className="flex flex-col divide-y divide-line rounded-xl border border-line bg-surface">
                                {entries.data.map((entry) => (
                                    <li
                                        key={entry.id}
                                        className="flex flex-col gap-1 px-4 py-3 text-sm"
                                    >
                                        <span className="flex flex-wrap items-baseline justify-between gap-x-3">
                                            <span className="font-mono font-semibold" dir="ltr">
                                                {entry.event}
                                            </span>
                                            <time
                                                dateTime={entry.at}
                                                className="text-xs text-muted"
                                            >
                                                {formatDate(entry.at, {
                                                    dateStyle: 'medium',
                                                    timeStyle: 'short',
                                                })}
                                            </time>
                                        </span>
                                        <span className="text-xs text-muted">
                                            {[
                                                entry.area,
                                                entry.actor ?? t('admin.audit.system'),
                                                entry.subjectType &&
                                                    `${entry.subjectType} ${entry.subjectId ?? ''}`,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </span>
                                        {entry.properties !== '[]' && entry.properties !== '{}' && (
                                            <details>
                                                <summary className="inline-flex min-h-11 cursor-pointer items-center text-xs font-semibold text-brand">
                                                    {t('admin.audit.details')}
                                                </summary>
                                                <pre
                                                    className="overflow-x-auto rounded-lg bg-surface-2 p-3 text-xs"
                                                    dir="ltr"
                                                >
                                                    {entry.properties}
                                                </pre>
                                            </details>
                                        )}
                                    </li>
                                ))}
                            </ol>
                        )}
                        <Pager page={entries} />
                    </div>
                </div>
            </div>
        </>
    );
}
