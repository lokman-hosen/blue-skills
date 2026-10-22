import { Head, useForm } from '@inertiajs/react';
import { Check, Circle, Phone, PhoneOff } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

type Lead = App.Domain.Crm.Data.LeadData;

type Props = {
    lead: Lead;
    agents: { id: string; name: string }[];
    statuses: App.Domain.Crm.Enums.FollowUpStatus[];
    channels: App.Domain.Crm.Enums.ContactChannel[];
    outcomes: App.Domain.Crm.Enums.ContactOutcome[];
};

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/** One lead: contact, progress, follow-up and every contact attempt (spec §9, D-095). */
export default function LeadShow({ lead, agents, statuses, channels, outcomes }: Props) {
    const { t, formatDate } = useTranslation();
    const contactable = lead.phone !== null;

    return (
        <>
            <Head title={lead.name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={[t(`lead_source.${lead.source}`), lead.campaign ?? lead.partner]
                        .filter(Boolean)
                        .join(' · ')}
                    title={lead.name}
                    description={
                        lead.passportReference
                            ? t('crm.lead.passport', { reference: lead.passportReference })
                            : t('crm.lead.not_signed_up')
                    }
                    back={{ href: admin.crm.index(), label: t('crm.title') }}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusChip status={lead.stage} icon={false} />
                            <StatusChip status={lead.followUp} />
                        </div>
                    }
                />
                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <div className="flex min-w-0 flex-col gap-5">
                        <Panel title={t('crm.lead.contact')}>
                            {contactable ? (
                                <div className="flex flex-col gap-2">
                                    <a
                                        href={`tel:${lead.phone}`}
                                        dir="ltr"
                                        className="inline-flex min-h-11 items-center gap-2 self-start font-mono text-lg font-semibold text-brand hover:underline"
                                    >
                                        <Phone aria-hidden className="size-4" />
                                        {lead.phone}
                                    </a>
                                    <p className="text-xs text-muted">
                                        {t('crm.lead.phone_logged')}
                                    </p>
                                </div>
                            ) : (
                                <p className="flex items-start gap-2 rounded-lg bg-surface-2 px-3 py-2 text-sm">
                                    <PhoneOff
                                        aria-hidden
                                        className="mt-0.5 size-4 shrink-0 text-bad"
                                    />
                                    {lead.optedOut
                                        ? t('crm.lead.opted_out')
                                        : t('crm.lead.do_not_contact')}
                                </p>
                            )}
                            <dl className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                <dt className="text-muted">{t('crm.field.country')}</dt>
                                <dd>{lead.countryCode ?? '—'}</dd>
                                <dt className="text-muted">{t('crm.lead.added')}</dt>
                                <dd>{formatDate(lead.createdAt)}</dd>
                                <dt className="text-muted">{t('crm.column.last_contact')}</dt>
                                <dd>
                                    {lead.lastContactedAt
                                        ? formatDate(lead.lastContactedAt)
                                        : t('crm.never_contacted')}
                                </dd>
                            </dl>
                        </Panel>

                        <Panel title={t('crm.lead.progress')}>
                            <p className="text-xs text-muted">{t('crm.lead.progress_hint')}</p>
                            <ol className="flex flex-col gap-1.5">
                                {lead.progress.map((step) => (
                                    <li
                                        key={step.stage}
                                        className={cn(
                                            'flex items-center gap-2 text-sm',
                                            !step.reached && 'text-muted',
                                        )}
                                    >
                                        {step.reached ? (
                                            <Check aria-hidden className="size-4 text-ok" />
                                        ) : (
                                            <Circle aria-hidden className="size-4" />
                                        )}
                                        {t(`status.lead_stage_${step.stage}`)}
                                        <span className="sr-only">
                                            {step.reached
                                                ? t('crm.lead.reached')
                                                : t('crm.lead.not_yet')}
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        </Panel>

                        <Panel title={t('crm.lead.history')}>
                            {lead.attempts.length === 0 ? (
                                <p className="text-sm text-muted">{t('crm.lead.no_attempts')}</p>
                            ) : (
                                <ol className="flex flex-col divide-y divide-line">
                                    {lead.attempts.map((attempt) => (
                                        <li key={attempt.id} className="flex flex-col gap-1 py-2.5">
                                            <span className="flex flex-wrap items-baseline justify-between gap-x-3 text-sm">
                                                <span className="font-semibold">
                                                    {t(`contact_channel.${attempt.channel}`)} ·{' '}
                                                    {t(`contact_outcome.${attempt.outcome}`)}
                                                </span>
                                                <time
                                                    dateTime={attempt.attemptedAt}
                                                    className="text-xs text-muted"
                                                >
                                                    {formatDate(attempt.attemptedAt, {
                                                        dateStyle: 'medium',
                                                        timeStyle: 'short',
                                                    })}
                                                </time>
                                            </span>
                                            {attempt.note && (
                                                <p className="text-sm whitespace-pre-line">
                                                    {attempt.note}
                                                </p>
                                            )}
                                            {attempt.staff && (
                                                <p className="text-xs text-muted">
                                                    {attempt.staff}
                                                </p>
                                            )}
                                        </li>
                                    ))}
                                </ol>
                            )}
                        </Panel>
                    </div>

                    <div className="flex flex-col gap-5">
                        {contactable && (
                            <AttemptForm leadId={lead.id} channels={channels} outcomes={outcomes} />
                        )}
                        <FollowUpForm lead={lead} agents={agents} statuses={statuses} />
                    </div>
                </div>
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="eyebrow text-muted">{title}</h2>
            {children}
        </section>
    );
}

function AttemptForm({
    leadId,
    channels,
    outcomes,
}: {
    leadId: string;
    channels: App.Domain.Crm.Enums.ContactChannel[];
    outcomes: App.Domain.Crm.Enums.ContactOutcome[];
}) {
    const { t } = useTranslation();
    const form = useForm({
        channel: 'phone_call',
        outcome: 'reached',
        note: '',
        next_follow_up_at: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(admin.crm.leads.attempts.store.url(leadId), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <Panel title={t('crm.attempt.title')}>
            <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                <div className="grid grid-cols-2 gap-3">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="attempt-channel">{t('crm.attempt.channel')}</Label>
                        <NativeSelect
                            id="attempt-channel"
                            value={form.data.channel}
                            onChange={(event) => form.setData('channel', event.target.value)}
                        >
                            {channels.map((value) => (
                                <option key={value} value={value}>
                                    {t(`contact_channel.${value}`)}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="attempt-outcome">{t('crm.attempt.outcome')}</Label>
                        <NativeSelect
                            id="attempt-outcome"
                            value={form.data.outcome}
                            onChange={(event) => form.setData('outcome', event.target.value)}
                            aria-invalid={!!form.errors.outcome}
                        >
                            {outcomes.map((value) => (
                                <option key={value} value={value}>
                                    {t(`contact_outcome.${value}`)}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                </div>
                <InputError message={form.errors.outcome} />
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="attempt-note">{t('crm.attempt.note')}</Label>
                    <textarea
                        id="attempt-note"
                        rows={3}
                        maxLength={1000}
                        value={form.data.note}
                        onChange={(event) => form.setData('note', event.target.value)}
                        className={TEXTAREA}
                    />
                    <InputError message={form.errors.note} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="attempt-next">{t('crm.field.next_follow_up')}</Label>
                    <Input
                        id="attempt-next"
                        type="date"
                        className="h-11"
                        value={form.data.next_follow_up_at}
                        onChange={(event) => form.setData('next_follow_up_at', event.target.value)}
                        aria-invalid={!!form.errors.next_follow_up_at}
                    />
                    <InputError message={form.errors.next_follow_up_at} />
                </div>
                <Button type="submit" className="min-h-11 self-start" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    {t('crm.attempt.save')}
                </Button>
            </form>
        </Panel>
    );
}

function FollowUpForm({
    lead,
    agents,
    statuses,
}: {
    lead: Lead;
    agents: { id: string; name: string }[];
    statuses: App.Domain.Crm.Enums.FollowUpStatus[];
}) {
    const { t } = useTranslation();
    const form = useForm({
        assigned_to: lead.assigneeId ?? '',
        follow_up_status: lead.followUpStatus as string,
        next_follow_up_at: lead.nextFollowUpAt ? lead.nextFollowUpAt.slice(0, 10) : '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(admin.crm.leads.update.url(lead.id), { preserveScroll: true });
    };

    return (
        <Panel title={t('crm.follow_up.title')}>
            <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="lead-assignee">{t('crm.column.assignee')}</Label>
                    <NativeSelect
                        id="lead-assignee"
                        value={form.data.assigned_to}
                        onChange={(event) => form.setData('assigned_to', event.target.value)}
                    >
                        <option value="">{t('crm.unassigned')}</option>
                        {agents.map((agent) => (
                            <option key={agent.id} value={agent.id}>
                                {agent.name}
                            </option>
                        ))}
                    </NativeSelect>
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="lead-status">{t('crm.column.follow_up')}</Label>
                    <NativeSelect
                        id="lead-status"
                        value={form.data.follow_up_status}
                        onChange={(event) => form.setData('follow_up_status', event.target.value)}
                        aria-invalid={!!form.errors.follow_up_status}
                    >
                        {statuses.map((value) => (
                            <option key={value} value={value}>
                                {t(`status.follow_up_${value}`)}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={form.errors.follow_up_status} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="lead-next">{t('crm.field.next_follow_up')}</Label>
                    <Input
                        id="lead-next"
                        type="date"
                        className="h-11"
                        value={form.data.next_follow_up_at}
                        onChange={(event) => form.setData('next_follow_up_at', event.target.value)}
                        aria-invalid={!!form.errors.next_follow_up_at}
                    />
                    <InputError message={form.errors.next_follow_up_at} />
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    className="min-h-11 self-start"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    {t('common.save')}
                </Button>
            </form>
        </Panel>
    );
}
