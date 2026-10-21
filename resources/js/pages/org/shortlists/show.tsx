import { Head, router } from '@inertiajs/react';
import { EyeOff, Star, Trash2 } from 'lucide-react';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { TalentCard } from '@/components/bs/talent-card';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    shortlist: App.Domain.Applications.Data.ShortlistData;
    entries: App.Domain.Applications.Data.ShortlistEntryData[];
};

/**
 * One shortlist. A worker who stops being discoverable stays on the list without details
 * (product rule 6).
 */
export default function ShortlistShow({ shortlist, entries }: Props) {
    const { t, formatDate } = useTranslation();

    const remove = (entryId: string) =>
        router.delete(org.shortlists.entries.destroy.url([shortlist.id, entryId]), {
            preserveScroll: true,
        });

    const removeButton = (entryId: string) => (
        <Button variant="ghost" size="sm" className="min-h-11" onClick={() => remove(entryId)}>
            {t('shortlists.remove')}
        </Button>
    );

    return (
        <>
            <Head title={shortlist.name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={shortlist.name}
                    description={
                        shortlist.jobTitle
                            ? `${shortlist.jobTitle} · ${shortlist.jobCode}`
                            : undefined
                    }
                    back={{ href: org.shortlists.index(), label: t('nav.shortlists') }}
                    actions={
                        <ConfirmDialog
                            trigger={
                                <Button variant="outline" className="min-h-11 text-bad">
                                    <Trash2 aria-hidden className="size-4" />
                                    {t('shortlists.delete')}
                                </Button>
                            }
                            title={t('shortlists.delete_title')}
                            description={t('shortlists.delete_body')}
                            confirmLabel={t('shortlists.delete')}
                            destructive
                            onConfirm={() =>
                                router.delete(org.shortlists.destroy.url(shortlist.id))
                            }
                        />
                    }
                />
                {entries.length === 0 ? (
                    <EmptyState
                        icon={Star}
                        title={t('shortlists.list_empty_title')}
                        description={t('shortlists.list_empty_body')}
                    />
                ) : (
                    <ul className="flex flex-col gap-3">
                        {entries.map((entry) => (
                            <li key={entry.id} className="flex flex-col gap-1">
                                {entry.worker ? (
                                    <TalentCard
                                        worker={entry.worker}
                                        actions={removeButton(entry.id)}
                                    />
                                ) : (
                                    <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-dashed border-line bg-surface p-4">
                                        <p className="flex items-center gap-2 text-sm text-muted">
                                            <EyeOff aria-hidden className="size-4" />
                                            {t('shortlists.hidden')}
                                        </p>
                                        {removeButton(entry.id)}
                                    </div>
                                )}
                                <p className="px-1 text-xs text-muted">
                                    {[
                                        entry.note,
                                        entry.addedBy &&
                                            t('shortlists.added_by', { name: entry.addedBy }),
                                        formatDate(entry.addedAt),
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
