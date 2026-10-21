import { Head, Link } from '@inertiajs/react';
import { BadgeCheck, GraduationCap } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    pools: App.Domain.Institute.Data.TalentPoolData[];
};

/** Talent pools that verified institutes published (spec §8, D-082). */
export default function InstitutePools({ pools }: Props) {
    const { t, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('nav.institute_pools')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.institute_pools')} description={t('pools.browse_body')} />
                {pools.length === 0 ? (
                    <EmptyState
                        icon={GraduationCap}
                        title={t('pools.browse_empty_title')}
                        description={t('pools.browse_empty_body')}
                    />
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {pools.map((pool) => (
                            <li key={pool.id}>
                                <Link
                                    href={org.institutePools.show(pool.id)}
                                    className="flex h-full flex-col gap-2 rounded-2xl border border-line bg-surface p-4 hover:border-brand/60 sm:p-5"
                                >
                                    <span className="text-lg font-semibold break-words text-brand">
                                        {pool.name}
                                    </span>
                                    <span className="inline-flex items-center gap-1.5 text-sm">
                                        {pool.institute}
                                        {pool.instituteVerified && (
                                            <BadgeCheck
                                                aria-label={t('pools.institute_verified')}
                                                className="size-4 text-ok"
                                            />
                                        )}
                                    </span>
                                    {pool.occupation && (
                                        <span className="text-sm text-muted">
                                            {pool.occupation}
                                        </span>
                                    )}
                                    {pool.description && (
                                        <span className="text-sm text-muted">
                                            {pool.description}
                                        </span>
                                    )}
                                    <span className="mt-auto text-sm font-semibold">
                                        {t('pools.graduates_count', {
                                            count: formatNumber(pool.visibleMembers),
                                        })}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
