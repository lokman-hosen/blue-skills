import { Head } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { TalentCard } from '@/components/bs/talent-card';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    pool: App.Domain.Institute.Data.TalentPoolData;
    workers: App.Domain.Applications.Data.TalentCardData[];
};

/** A published institute pool: graduates who chose to be found, as talent cards (D-082). */
export default function InstitutePoolShow({ pool, workers }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={pool.name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={pool.institute}
                    title={pool.name}
                    description={pool.description ?? undefined}
                    back={{ href: org.institutePools.index(), label: t('nav.institute_pools') }}
                />
                <p className="rounded-xl bg-surface-2 px-4 py-3 text-sm">
                    {t('pools.browse_note')}
                </p>
                {workers.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title={t('pools.browse_no_members')}
                        description={t('pools.browse_no_members_body')}
                    />
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {workers.map((worker) => (
                            <li key={worker.reference}>
                                <TalentCard worker={worker} />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
