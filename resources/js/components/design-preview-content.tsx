import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { DataTableColumn } from '@/components/bs/data-table';
import { DataTable } from '@/components/bs/data-table';
import { KpiTile } from '@/components/bs/kpi-tile';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import design from '@/routes/design';

type Row = {
    id: string;
    title: string;
    code: string;
    status: App.Support.Data.StatusData;
    applicants: number;
};

const rows: Row[] = [
    {
        id: '1',
        title: 'Building Electrician',
        code: 'UAE-EL-0001',
        status: { value: 'published', label: 'Published', tone: 'ok' },
        applicants: 1240,
    },
    {
        id: '2',
        title: 'Warehouse Professional',
        code: 'PL-WH-0014',
        status: { value: 'under_review', label: 'Under review', tone: 'warn' },
        applicants: 310,
    },
    {
        id: '3',
        title: 'MIG/MAG Welder',
        code: 'CZ-WE-0003',
        status: { value: 'draft', label: 'Draft', tone: 'neutral' },
        applicants: 0,
    },
];

/**
 * Sample content used to review each layout at /_design/layouts/{layout}.
 */
export function DesignPreviewContent({ layout }: { layout: string }) {
    const { t, formatNumber } = useTranslation();
    const columns: DataTableColumn<Row>[] = [
        {
            key: 'title',
            header: t('nav.jobs'),
            primary: true,
            cell: (row) => (
                <span className="flex flex-col">
                    <span className="font-semibold">{row.title}</span>
                    <span className="font-mono text-[11px] text-muted">{row.code}</span>
                </span>
            ),
        },
        { key: 'status', header: 'Status', cell: (row) => <StatusChip status={row.status} /> },
        {
            key: 'applicants',
            header: t('nav.applications'),
            align: 'end',
            cell: (row) => formatNumber(row.applicants),
        },
    ];

    return (
        <>
            <Head title={`${layout} layout preview`} />
            <div className="flex flex-col gap-6">
                <PageHeader
                    eyebrow={`${layout} layout`}
                    title={t('nav.dashboard')}
                    description={t('app.tagline')}
                    back={{ href: design.index(), label: t('nav.design_system') }}
                    actions={
                        <Button asChild className="min-h-11">
                            <Link href={design.index()}>
                                <Plus aria-hidden />
                                {t('nav.jobs')}
                            </Link>
                        </Button>
                    }
                />
                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <KpiTile label={t('nav.jobs')} value={formatNumber(12)} />
                    <KpiTile label={t('nav.applications')} value={formatNumber(3450)} />
                    <KpiTile label={t('nav.shortlists')} value={formatNumber(620)} />
                    <KpiTile label={t('nav.mobility')} value={formatNumber(98)} />
                </div>
                <DataTable
                    caption={t('nav.jobs')}
                    columns={columns}
                    rows={rows}
                    rowKey={(row) => row.id}
                />
            </div>
        </>
    );
}
