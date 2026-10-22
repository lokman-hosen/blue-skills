import { Head, Link, router } from '@inertiajs/react';
import { Building } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { BillingTabs } from '@/components/bs/billing/billing-tabs';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import type { Paginated } from '@/components/bs/jobs/types';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Row = {
    id: string;
    name: string;
    type: string;
    plan: string | null;
    credits: number;
    unpaid: App.Support.Data.MoneyData;
};

type Props = { organizations: Paginated<Row>; q: string | null };

/** Every organization's plan, credits and unpaid invoices (spec §11). */
export default function BillingOrganizations({ organizations, q }: Props) {
    const { t, formatMoney, formatNumber } = useTranslation();
    const [search, setSearch] = useState(q ?? '');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(
            admin.billing.index.url({ query: search ? { q: search } : {} }),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={t('nav.billing')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.billing')} description={t('billing.admin_body')} />
                <BillingTabs active="organizations" />
                <form onSubmit={submit} className="flex flex-wrap items-end gap-2" role="search">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="billing-q">{t('billing.search')}</Label>
                        <Input
                            id="billing-q"
                            className="h-11 w-64"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                    <Button type="submit" variant="outline" className="min-h-11">
                        {t('common.show_results')}
                    </Button>
                </form>
                <DataTable<Row>
                    caption={t('billing.tab.organizations')}
                    rows={organizations.data}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={Building}
                            title={t('billing.no_organizations')}
                            description={t('billing.no_organizations_body')}
                        />
                    }
                    columns={[
                        {
                            key: 'name',
                            header: t('billing.organization'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={admin.billing.organizations.show(row.id)}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    {row.name}
                                    <span className="block text-xs font-normal text-muted">
                                        {t(`organization_type.${row.type}`)}
                                    </span>
                                </Link>
                            ),
                        },
                        {
                            key: 'plan',
                            header: t('billing.plan'),
                            cell: (row) =>
                                row.plan ?? (
                                    <span className="text-muted">{t('billing.no_plan')}</span>
                                ),
                        },
                        {
                            key: 'credits',
                            header: t('billing.credits'),
                            align: 'end',
                            cell: (row) => (
                                <span
                                    className={
                                        row.credits < 0 ? 'font-semibold text-bad tnum' : 'tnum'
                                    }
                                >
                                    {formatNumber(row.credits)}
                                </span>
                            ),
                        },
                        {
                            key: 'unpaid',
                            header: t('billing.unpaid'),
                            align: 'end',
                            cell: (row) =>
                                row.unpaid.amountMinor > 0 ? (
                                    <span className="tnum">{formatMoney(row.unpaid)}</span>
                                ) : (
                                    <span className="text-muted">—</span>
                                ),
                        },
                    ]}
                />
                <Pager page={organizations} />
            </div>
        </>
    );
}
