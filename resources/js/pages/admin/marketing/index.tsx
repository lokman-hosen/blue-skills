import { Head, Link } from '@inertiajs/react';
import { Megaphone } from 'lucide-react';
import type { CampaignFormOptions } from '@/components/bs/crm/campaign-dialog';
import { CampaignDialog } from '@/components/bs/crm/campaign-dialog';
import { FUNNEL_STEPS } from '@/components/bs/crm/funnel-bars';
import { RangeForm } from '@/components/bs/crm/range-form';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { KpiTile } from '@/components/bs/kpi-tile';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Campaign = App.Domain.Crm.Data.CampaignData;
type Funnel = App.Domain.Crm.Data.FunnelData;

type Props = CampaignFormOptions & {
    campaigns: Campaign[];
    total: Funnel;
    withoutCampaign: Funnel;
    range: { from: string; to: string };
};

/** Campaigns and their funnels (spec §9 "Campaign analytics"). */
export default function Marketing({
    campaigns,
    total,
    withoutCampaign,
    range,
    channels,
    partners,
    countries,
}: Props) {
    const { t, formatNumber } = useTranslation();
    const options = { channels, partners, countries };

    return (
        <>
            <Head title={t('marketing.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('marketing.title')}
                    description={t('marketing.body')}
                    actions={<CampaignDialog {...options} />}
                />
                <RangeForm
                    range={range}
                    url={(next) => admin.marketing.index.url({ query: next })}
                />
                <section aria-labelledby="totals-heading" className="flex flex-col gap-3">
                    <h2 id="totals-heading" className="eyebrow text-muted">
                        {t('marketing.all_campaigns')}
                    </h2>
                    <ul className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {(['visitors', 'registrations', 'qualified', 'deployed'] as const).map(
                            (step) => (
                                <li key={step}>
                                    <KpiTile
                                        label={t(`marketing.step.${step}`)}
                                        value={formatNumber(total[step])}
                                    />
                                </li>
                            ),
                        )}
                    </ul>
                </section>
                <DataTable<Campaign>
                    caption={t('marketing.title')}
                    rows={campaigns}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={Megaphone}
                            title={t('marketing.empty_title')}
                            description={t('marketing.empty_body')}
                            action={<CampaignDialog {...options} />}
                        />
                    }
                    columns={[
                        {
                            key: 'campaign',
                            header: t('marketing.column.campaign'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={admin.marketing.show(row.id)}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    {row.name}
                                    <span className="block text-xs font-normal text-muted">
                                        <span dir="ltr" className="font-mono">
                                            {row.code}
                                        </span>
                                        {' · '}
                                        {t(`campaign_channel.${row.channel}`)}
                                    </span>
                                </Link>
                            ),
                        },
                        {
                            key: 'status',
                            header: t('admin.organizations.status'),
                            cell: (row) => <StatusChip status={row.status} />,
                        },
                        ...FUNNEL_STEPS.filter((step) => step !== 'leads').map((step) => ({
                            key: step,
                            header: t(`marketing.step.${step}`),
                            align: 'end' as const,
                            cell: (row: Campaign) => (
                                <span className="tnum">{formatNumber(row.funnel[step])}</span>
                            ),
                        })),
                    ]}
                />
                <p className="rounded-lg bg-surface-2 px-3 py-2 text-sm text-muted">
                    {t('marketing.without_campaign', {
                        registrations: formatNumber(withoutCampaign.registrations),
                        deployed: formatNumber(withoutCampaign.deployed),
                    })}
                </p>
                <p className="text-xs text-muted">{t('marketing.cohort_note')}</p>
            </div>
        </>
    );
}
