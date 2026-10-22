import { Head, router } from '@inertiajs/react';
import { Check, Copy, Download, Pause, Play, Square } from 'lucide-react';
import type { ReactNode } from 'react';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import type { CampaignFormOptions } from '@/components/bs/crm/campaign-dialog';
import { CampaignDialog } from '@/components/bs/crm/campaign-dialog';
import { FunnelBars } from '@/components/bs/crm/funnel-bars';
import { RangeForm } from '@/components/bs/crm/range-form';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useClipboard } from '@/hooks/use-clipboard';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Campaign = App.Domain.Crm.Data.CampaignData;

type Props = CampaignFormOptions & {
    campaign: Campaign;
    visits: { day: string; visits: number }[];
    qrSvg: string;
    range: { from: string; to: string };
};

/** One campaign: its link and QR code, funnel and daily visitors (spec §9). */
export default function CampaignShow({
    campaign,
    visits,
    qrSvg,
    range,
    channels,
    partners,
    countries,
}: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const [copied, copy] = useClipboard();
    const status = campaign.status.value.replace('campaign_', '');
    const maxVisits = Math.max(1, ...visits.map((visit) => visit.visits));
    const act = (action: 'pause' | 'resume' | 'end') =>
        router.post(
            admin.marketing.status.url([campaign.id, action]),
            {},
            { preserveScroll: true },
        );

    return (
        <>
            <Head title={campaign.name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={[
                        t(`campaign_channel.${campaign.channel}`),
                        campaign.partner,
                        campaign.sourceCountryCode,
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                    title={campaign.name}
                    description={
                        campaign.endsOn
                            ? t('marketing.runs', {
                                  start: formatDate(campaign.startsOn),
                                  end: formatDate(campaign.endsOn),
                              })
                            : t('marketing.runs_from', { start: formatDate(campaign.startsOn) })
                    }
                    back={{ href: admin.marketing.index(), label: t('marketing.title') }}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusChip status={campaign.status} />
                            {status === 'active' && (
                                <Button
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() => act('pause')}
                                >
                                    <Pause aria-hidden className="size-4" />
                                    {t('marketing.pause')}
                                </Button>
                            )}
                            {status === 'paused' && (
                                <Button
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() => act('resume')}
                                >
                                    <Play aria-hidden className="size-4" />
                                    {t('marketing.resume')}
                                </Button>
                            )}
                            {status !== 'ended' && (
                                <>
                                    <CampaignDialog
                                        campaign={campaign}
                                        channels={channels}
                                        partners={partners}
                                        countries={countries}
                                    />
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="ghost" className="min-h-11 text-bad">
                                                <Square aria-hidden className="size-4" />
                                                {t('marketing.end')}
                                            </Button>
                                        }
                                        title={t('marketing.end_title', { name: campaign.name })}
                                        description={t('marketing.end_hint')}
                                        confirmLabel={t('marketing.end')}
                                        destructive
                                        onConfirm={() => act('end')}
                                    />
                                </>
                            )}
                        </div>
                    }
                />
                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div className="flex min-w-0 flex-col gap-5">
                        <RangeForm
                            range={range}
                            url={(next) => admin.marketing.show.url(campaign.id, { query: next })}
                        />
                        <Panel title={t('marketing.funnel')}>
                            <FunnelBars funnel={campaign.funnel} />
                            <p className="text-xs text-muted">{t('marketing.cohort_note')}</p>
                        </Panel>
                        <Panel title={t('marketing.daily_visitors')}>
                            {visits.length === 0 ? (
                                <p className="text-sm text-muted">{t('marketing.no_visits')}</p>
                            ) : (
                                <table className="w-full text-sm">
                                    <caption className="sr-only">
                                        {t('marketing.daily_visitors')}
                                    </caption>
                                    <thead className="sr-only">
                                        <tr>
                                            <th scope="col">{t('marketing.day')}</th>
                                            <th scope="col">{t('marketing.step.visitors')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {visits.map((visit) => (
                                            <tr key={visit.day}>
                                                <th
                                                    scope="row"
                                                    className="w-28 py-1 pe-3 text-start font-normal whitespace-nowrap text-muted"
                                                >
                                                    {formatDate(visit.day)}
                                                </th>
                                                <td className="py-1">
                                                    <span className="flex items-center gap-2">
                                                        <span
                                                            aria-hidden
                                                            className="h-2 rounded-full bg-brand"
                                                            style={{
                                                                width: `${Math.max(2, (visit.visits / maxVisits) * 100)}%`,
                                                            }}
                                                        />
                                                        <span className="tnum">
                                                            {formatNumber(visit.visits)}
                                                        </span>
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </Panel>
                    </div>
                    <Panel title={t('marketing.share')}>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="campaign-link">{t('marketing.link')}</Label>
                            <div className="flex gap-2">
                                <Input
                                    id="campaign-link"
                                    readOnly
                                    value={campaign.link}
                                    className="h-11 font-mono text-sm"
                                    dir="ltr"
                                    onFocus={(event) => event.target.select()}
                                />
                                <Button
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() => copy(campaign.link)}
                                    aria-label={t('marketing.copy_link')}
                                >
                                    {copied === campaign.link ? (
                                        <Check aria-hidden className="size-4 text-ok" />
                                    ) : (
                                        <Copy aria-hidden className="size-4" />
                                    )}
                                </Button>
                            </div>
                            <p className="text-xs text-muted">
                                {t(`marketing.landing_hint.${campaign.landing}`)}
                            </p>
                        </div>
                        {/* Server-generated QR markup for this campaign's link. */}
                        <div
                            className="mx-auto w-full max-w-48 rounded-lg bg-white p-2 [&_svg]:h-auto [&_svg]:w-full"
                            role="img"
                            aria-label={t('marketing.qr_label', { url: campaign.link })}
                            dangerouslySetInnerHTML={{ __html: qrSvg }}
                        />
                        <Button asChild variant="outline" className="min-h-11">
                            <a href={admin.marketing.qr.url(campaign.id)} download>
                                <Download aria-hidden className="size-4" />
                                {t('marketing.qr_download')}
                            </a>
                        </Button>
                    </Panel>
                </div>
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-3 self-start rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="eyebrow text-muted">{title}</h2>
            {children}
        </section>
    );
}
