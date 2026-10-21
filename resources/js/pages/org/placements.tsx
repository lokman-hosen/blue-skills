import { Head } from '@inertiajs/react';
import { Plane } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { KpiTile } from '@/components/bs/kpi-tile';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';

type Placement = App.Domain.Institute.Data.PlacementData;

type Props = {
    placements: Placement[];
    counts: { sharing: number; interest: number; applied: number; placed: number };
};

/**
 * Employer interest and placements of graduates who share their progress (D-082). Graduates who
 * don't share it aren't listed.
 */
export default function Placements({ placements, counts }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('nav.placements')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.placements')} description={t('placements.body')} />
                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <KpiTile
                        label={t('placements.kpi.sharing')}
                        value={formatNumber(counts.sharing)}
                    />
                    <KpiTile
                        label={t('placements.kpi.interest')}
                        value={formatNumber(counts.interest)}
                    />
                    <KpiTile
                        label={t('placements.kpi.applied')}
                        value={formatNumber(counts.applied)}
                    />
                    <KpiTile
                        label={t('placements.kpi.placed')}
                        value={formatNumber(counts.placed)}
                    />
                </div>
                {placements.length === 0 ? (
                    <EmptyState
                        icon={Plane}
                        title={t('placements.empty_title')}
                        description={t('placements.empty_body')}
                    />
                ) : (
                    <ul className="flex flex-col gap-4">
                        {placements.map((row) => (
                            <li
                                key={row.memberId}
                                className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:p-5"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <p className="font-semibold">{row.name}</p>
                                        <p className="text-xs text-muted">
                                            <span dir="ltr" className="font-mono">
                                                {row.passportReference}
                                            </span>
                                            {` · ${row.course} · ${row.cohort}`}
                                        </p>
                                    </div>
                                    {row.placed && (
                                        <StatusChip tone="ok" label={t('placements.placed')} />
                                    )}
                                </div>
                                {row.interest.length > 0 && (
                                    <div className="flex flex-col gap-1">
                                        <h3 className="eyebrow text-muted">
                                            {t('placements.interest')}
                                        </h3>
                                        <ul className="flex flex-col gap-1 text-sm">
                                            {row.interest.map((item, index) => (
                                                <li key={index}>
                                                    {t('placements.invited_by', {
                                                        organization: item.organization,
                                                        job: item.job,
                                                    })}
                                                    {item.sentAt && (
                                                        <span className="text-muted">
                                                            {' '}
                                                            · {formatDate(item.sentAt)}
                                                        </span>
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                <div className="flex flex-col gap-1">
                                    <h3 className="eyebrow text-muted">
                                        {t('placements.applications')}
                                    </h3>
                                    {row.applications.length === 0 ? (
                                        <p className="text-sm text-muted">
                                            {t('placements.no_applications')}
                                        </p>
                                    ) : (
                                        <ul className="flex flex-col divide-y divide-line">
                                            {row.applications.map((application) => (
                                                <li
                                                    key={application.jobCode}
                                                    className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm"
                                                >
                                                    <span className="min-w-0">
                                                        <span className="font-semibold">
                                                            {application.job}
                                                        </span>
                                                        <span className="block text-xs text-muted">
                                                            {application.employer} ·{' '}
                                                            {formatDate(application.appliedAt)}
                                                        </span>
                                                    </span>
                                                    <span className="flex flex-wrap items-center gap-1.5">
                                                        <StatusChip status={application.status} />
                                                        {application.caseStatus && (
                                                            <StatusChip
                                                                status={application.caseStatus}
                                                            />
                                                        )}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
