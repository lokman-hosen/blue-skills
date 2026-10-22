import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Users } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterPill } from '@/components/bs/filter-pill';
import { FilterSheet } from '@/components/bs/filter-sheet';
import type { Paginated } from '@/components/bs/jobs/types';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

type LeadRow = App.Domain.Crm.Data.LeadListItemData;
type View = 'all' | 'due' | 'mine' | 'unassigned';
type Filters = {
    status: string | null;
    stage: string | null;
    source: string | null;
    campaign: string | null;
    q: string | null;
};
type Option = { id: string; name: string };

type Props = {
    leads: Paginated<LeadRow>;
    view: View;
    filters: Filters;
    campaigns: Option[];
    countries: string[];
};

const VIEWS: View[] = ['all', 'due', 'mine', 'unassigned'];
const STATUSES = ['new', 'contacted', 'no_answer', 'call_back', 'not_interested', 'do_not_contact'];
const STAGES = [
    'prospect',
    'registered',
    'profile_complete',
    'qualified',
    'applied',
    'selected',
    'deployed',
];
const SOURCES = ['campaign', 'institute', 'direct', 'walk_in', 'call_centre', 'referral'];
const EMPTY: Filters = { status: null, stage: null, source: null, campaign: null, q: null };

/** The worker CRM: leads, follow-ups and conversion stages (spec §9, D-095). */
export default function Leads({ leads, view, filters, campaigns, countries }: Props) {
    const { t, formatDate } = useTranslation();
    const [draft, setDraft] = useState<Filters>(filters);
    const active = Object.values(filters).filter(Boolean).length;

    const query = (nextView: View, next: Filters) =>
        Object.fromEntries(
            Object.entries({ view: nextView === 'all' ? null : nextView, ...next }).filter(
                ([, value]) => value,
            ),
        ) as Record<string, string>;

    const apply = (next: Filters) =>
        router.get(admin.crm.index.url({ query: query(view, next) }), {}, { preserveScroll: true });

    const select = (key: keyof Filters, values: string[], label: (value: string) => string) => (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={`crm-${key}`}>{t(`crm.filter.${key}`)}</Label>
            <NativeSelect
                id={`crm-${key}`}
                value={draft[key] ?? ''}
                onChange={(event) => setDraft({ ...draft, [key]: event.target.value || null })}
            >
                <option value="">{t('crm.filter.any')}</option>
                {values.map((value) => (
                    <option key={value} value={value}>
                        {label(value)}
                    </option>
                ))}
            </NativeSelect>
        </div>
    );

    return (
        <>
            <Head title={t('crm.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('crm.title')}
                    description={t('crm.body')}
                    actions={<AddLeadDialog campaigns={campaigns} countries={countries} />}
                />
                <nav aria-label={t('crm.title')} className="flex flex-wrap gap-1.5">
                    {VIEWS.map((value) => (
                        <FilterPill
                            key={value}
                            href={admin.crm.index({ query: query(value, filters) })}
                            active={view === value}
                            label={t(`crm.view.${value}`)}
                        />
                    ))}
                </nav>
                <div className="grid gap-5 md:grid-cols-[16rem_minmax(0,1fr)]">
                    <FilterSheet
                        activeCount={active}
                        onApply={() => apply(draft)}
                        onClear={() => apply(EMPTY)}
                        className="self-start"
                    >
                        <div className="flex flex-col gap-4">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="crm-q">{t('crm.filter.q')}</Label>
                                <Input
                                    id="crm-q"
                                    className="h-11"
                                    value={draft.q ?? ''}
                                    onChange={(event) =>
                                        setDraft({ ...draft, q: event.target.value || null })
                                    }
                                />
                            </div>
                            {select('status', STATUSES, (value) => t(`status.follow_up_${value}`))}
                            {select('stage', STAGES, (value) => t(`status.lead_stage_${value}`))}
                            {select('source', SOURCES, (value) => t(`lead_source.${value}`))}
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="crm-campaign">{t('crm.filter.campaign')}</Label>
                                <NativeSelect
                                    id="crm-campaign"
                                    value={draft.campaign ?? ''}
                                    onChange={(event) =>
                                        setDraft({ ...draft, campaign: event.target.value || null })
                                    }
                                >
                                    <option value="">{t('crm.filter.any')}</option>
                                    {campaigns.map((campaign) => (
                                        <option key={campaign.id} value={campaign.id}>
                                            {campaign.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </div>
                            <Button
                                className="hidden min-h-11 md:inline-flex"
                                onClick={() => apply(draft)}
                            >
                                {t('common.show_results')}
                            </Button>
                        </div>
                    </FilterSheet>
                    <div className="flex min-w-0 flex-col gap-3">
                        <DataTable<LeadRow>
                            caption={t('crm.title')}
                            rows={leads.data}
                            rowKey={(row) => row.id}
                            empty={
                                <EmptyState
                                    icon={Users}
                                    title={t('crm.empty_title')}
                                    description={t('crm.empty_body')}
                                />
                            }
                            columns={[
                                {
                                    key: 'lead',
                                    header: t('crm.column.lead'),
                                    primary: true,
                                    cell: (row) => (
                                        <Link
                                            href={admin.crm.leads.show(row.id)}
                                            className="font-semibold text-brand hover:underline"
                                        >
                                            {row.name}
                                            <span className="block text-xs font-normal text-muted">
                                                {[
                                                    t(`lead_source.${row.source}`),
                                                    row.campaign,
                                                    row.countryCode,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </span>
                                        </Link>
                                    ),
                                },
                                {
                                    key: 'stage',
                                    header: t('crm.column.stage'),
                                    cell: (row) => <StatusChip status={row.stage} icon={false} />,
                                },
                                {
                                    key: 'follow_up',
                                    header: t('crm.column.follow_up'),
                                    cell: (row) => <StatusChip status={row.followUp} />,
                                },
                                {
                                    key: 'next',
                                    header: t('crm.column.next'),
                                    cell: (row) =>
                                        row.nextFollowUpAt ? (
                                            <span
                                                className={cn(
                                                    row.overdue && 'font-semibold text-bad',
                                                )}
                                            >
                                                {formatDate(row.nextFollowUpAt)}
                                                {row.overdue && (
                                                    <span className="block text-xs">
                                                        {t('crm.overdue')}
                                                    </span>
                                                )}
                                            </span>
                                        ) : (
                                            <span className="text-muted">—</span>
                                        ),
                                },
                                {
                                    key: 'assignee',
                                    header: t('crm.column.assignee'),
                                    cell: (row) =>
                                        row.assignee ?? (
                                            <span className="text-muted">
                                                {t('crm.unassigned')}
                                            </span>
                                        ),
                                },
                                {
                                    key: 'last',
                                    header: t('crm.column.last_contact'),
                                    cell: (row) =>
                                        row.lastContactedAt ? (
                                            formatDate(row.lastContactedAt)
                                        ) : (
                                            <span className="text-muted">
                                                {t('crm.never_contacted')}
                                            </span>
                                        ),
                                },
                            ]}
                        />
                        <Pager page={leads} />
                    </div>
                </div>
            </div>
        </>
    );
}

/** A lead met at a job fair, by phone or through a referral. */
function AddLeadDialog({ campaigns, countries }: { campaigns: Option[]; countries: string[] }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: '',
        country: countries[0] ?? 'BD',
        phone: '',
        source: 'walk_in',
        campaign_id: '',
        assign_to_me: true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(admin.crm.leads.store.url(), { onSuccess: () => setOpen(false) });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button className="min-h-11">
                    <Plus aria-hidden className="size-4" />
                    {t('crm.add')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('crm.add')}</DialogTitle>
                    <DialogDescription>{t('crm.add_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="lead-name">{t('crm.field.name')}</Label>
                        <Input
                            id="lead-name"
                            className="h-11"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            aria-invalid={!!form.errors.name}
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-3 sm:grid-cols-[8rem_minmax(0,1fr)]">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="lead-country">{t('crm.field.country')}</Label>
                            <NativeSelect
                                id="lead-country"
                                value={form.data.country}
                                onChange={(event) => form.setData('country', event.target.value)}
                            >
                                {countries.map((code) => (
                                    <option key={code} value={code}>
                                        {code}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="lead-phone">{t('crm.field.phone')}</Label>
                            <Input
                                id="lead-phone"
                                type="tel"
                                dir="ltr"
                                inputMode="tel"
                                autoComplete="off"
                                className="h-11"
                                value={form.data.phone}
                                onChange={(event) => form.setData('phone', event.target.value)}
                                aria-invalid={!!form.errors.phone}
                            />
                            <InputError message={form.errors.phone} />
                        </div>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="lead-source">{t('crm.field.source')}</Label>
                        <NativeSelect
                            id="lead-source"
                            value={form.data.source}
                            onChange={(event) => form.setData('source', event.target.value)}
                        >
                            {['walk_in', 'call_centre', 'referral', 'campaign'].map((value) => (
                                <option key={value} value={value}>
                                    {t(`lead_source.${value}`)}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    {form.data.source === 'campaign' && (
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="lead-campaign">{t('crm.field.campaign')}</Label>
                            <NativeSelect
                                id="lead-campaign"
                                value={form.data.campaign_id}
                                onChange={(event) =>
                                    form.setData('campaign_id', event.target.value)
                                }
                                aria-invalid={!!form.errors.campaign_id}
                            >
                                <option value="">{t('crm.field.choose_campaign')}</option>
                                {campaigns.map((campaign) => (
                                    <option key={campaign.id} value={campaign.id}>
                                        {campaign.name}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={form.errors.campaign_id} />
                        </div>
                    )}
                    <div className="flex min-h-11 items-center gap-2">
                        <Checkbox
                            id="lead-mine"
                            checked={form.data.assign_to_me}
                            onCheckedChange={(checked) =>
                                form.setData('assign_to_me', checked === true)
                            }
                        />
                        <Label htmlFor="lead-mine" className="font-normal">
                            {t('crm.field.assign_to_me')}
                        </Label>
                    </div>
                    <Button
                        type="submit"
                        className="min-h-11 self-start"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        {t('crm.add')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
