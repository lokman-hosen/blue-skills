import { Head, router, useForm } from '@inertiajs/react';
import { Check, Copy, Download, Pencil } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { CohortFields } from '@/components/bs/institute/cohort-fields';
import { GraduateTable } from '@/components/bs/institute/graduate-table';
import { KpiTile } from '@/components/bs/kpi-tile';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { useClipboard } from '@/hooks/use-clipboard';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Cohort = App.Domain.Institute.Data.CohortData;

type Abilities = { manage: boolean; invite: boolean; attest: boolean; assess: boolean };

type Props = {
    cohort: Cohort;
    abilities: Abilities;
    instituteVerified: boolean;
};

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/** A cohort: who joined and how to invite graduates (prototype i-cohort, D-082). */
export default function CohortShow({ cohort, abilities, instituteVerified }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const summary = cohort.summary;
    const open = summary.status.value === 'cohort_open';

    return (
        <>
            <Head title={`${summary.name} · ${summary.courseTitle}`} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={t('cohorts.course_eyebrow', { course: summary.courseTitle })}
                    title={summary.name}
                    description={
                        summary.startsOn && summary.endsOn
                            ? t('cohorts.dates', {
                                  start: formatDate(summary.startsOn),
                                  end: formatDate(summary.endsOn),
                              })
                            : undefined
                    }
                    back={{ href: org.courses.index(), label: t('nav.courses') }}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusChip status={summary.status} />
                            {abilities.manage && <EditCohort cohort={cohort} />}
                            {abilities.manage && (
                                <Button
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() =>
                                        router.post(
                                            org.cohorts.status.url([
                                                summary.id,
                                                open ? 'closed' : 'open',
                                            ]),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {open ? t('cohorts.close') : t('cohorts.reopen')}
                                </Button>
                            )}
                        </div>
                    }
                />

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <KpiTile label={t('cohorts.kpi.joined')} value={formatNumber(summary.joined)} />
                    <KpiTile
                        label={t('cohorts.kpi.waiting')}
                        value={formatNumber(summary.invited)}
                    />
                    <KpiTile
                        label={t('cohorts.kpi.skillshows')}
                        value={formatNumber(cohort.skillShows)}
                    />
                    <KpiTile
                        label={t('cohorts.kpi.sharing')}
                        value={formatNumber(
                            cohort.members.filter((member) => member.sharesProgress).length,
                        )}
                    />
                </div>

                {open && abilities.invite ? (
                    <InvitePanel cohort={cohort} />
                ) : (
                    !open && (
                        <p className="rounded-xl border border-line bg-surface-2 px-4 py-3 text-sm">
                            {t('cohorts.closed_note')}
                        </p>
                    )
                )}

                <section className="flex flex-col gap-3">
                    <h2 className="eyebrow text-muted">{t('cohorts.graduates')}</h2>
                    {!instituteVerified && (
                        <p className="rounded-xl border border-warn/40 bg-warn-soft px-4 py-3 text-sm">
                            {t('institute.records.needs_verification')}
                        </p>
                    )}
                    <GraduateTable members={cohort.members} abilities={abilities} />
                    <p className="text-xs text-muted">{t('institute.records.note')}</p>
                </section>
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="font-semibold">{title}</h2>
            {children}
        </section>
    );
}

function InvitePanel({ cohort }: { cohort: Cohort }) {
    const { t } = useTranslation();
    const [copied, copy] = useClipboard();
    const [message, setMessage] = useState(cohort.whatsappText);

    return (
        <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_16rem]">
            <Panel title={t('cohorts.invite.title')}>
                <p className="text-sm text-muted">{t('cohorts.invite.body')}</p>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="invite-link">{t('cohorts.invite.link')}</Label>
                    <div className="flex gap-2">
                        <Input
                            id="invite-link"
                            readOnly
                            value={cohort.inviteUrl}
                            className="h-11 font-mono text-sm"
                            dir="ltr"
                            onFocus={(event) => event.target.select()}
                        />
                        <Button
                            variant="outline"
                            className="min-h-11"
                            onClick={() => copy(cohort.inviteUrl)}
                            aria-label={t('cohorts.invite.copy_link')}
                        >
                            {copied === cohort.inviteUrl ? (
                                <Check aria-hidden className="size-4 text-ok" />
                            ) : (
                                <Copy aria-hidden className="size-4" />
                            )}
                        </Button>
                    </div>
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="invite-whatsapp">{t('cohorts.invite.whatsapp')}</Label>
                    <textarea
                        id="invite-whatsapp"
                        rows={3}
                        value={message}
                        onChange={(event) => setMessage(event.target.value)}
                        className={TEXTAREA}
                    />
                    <Button className="min-h-11 self-start" onClick={() => copy(message)}>
                        {copied === message ? (
                            <Check aria-hidden className="size-4" />
                        ) : (
                            <Copy aria-hidden className="size-4" />
                        )}
                        {copied === message
                            ? t('cohorts.invite.copied')
                            : t('cohorts.invite.copy_whatsapp')}
                    </Button>
                </div>
                <RosterForm cohort={cohort} />
            </Panel>
            <Panel title={t('cohorts.invite.qr')}>
                {/* Server-generated QR markup for this cohort's link. */}
                <div
                    className="mx-auto w-full max-w-48 rounded-lg bg-white p-2 [&_svg]:h-auto [&_svg]:w-full"
                    role="img"
                    aria-label={t('cohorts.invite.qr_label', { url: cohort.inviteUrl })}
                    dangerouslySetInnerHTML={{ __html: cohort.qrSvg }}
                />
                <p className="text-center text-xs text-muted">{t('cohorts.invite.qr_hint')}</p>
                <Button asChild variant="outline" className="min-h-11">
                    <a href={org.cohorts.qr.url(cohort.summary.id)} download>
                        <Download aria-hidden className="size-4" />
                        {t('cohorts.invite.qr_download')}
                    </a>
                </Button>
            </Panel>
        </div>
    );
}

function RosterForm({ cohort }: { cohort: Cohort }) {
    const { t } = useTranslation();
    const form = useForm({ roster: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.cohorts.roster.url(cohort.summary.id), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-1.5 border-t border-line pt-3"
            noValidate
        >
            <Label htmlFor="roster">{t('cohorts.roster.label')}</Label>
            <p id="roster-hint" className="text-xs text-muted">
                {t('cohorts.roster.hint')}
            </p>
            <textarea
                id="roster"
                rows={4}
                value={form.data.roster}
                onChange={(event) => form.setData('roster', event.target.value)}
                className={`${TEXTAREA} font-mono`}
                aria-describedby="roster-hint"
                placeholder={'Rahim Uddin, 01711000001\nKarim Ahmed, +8801711000002'}
                dir="ltr"
            />
            <InputError message={form.errors.roster} />
            <Button
                type="submit"
                variant="outline"
                className="min-h-11 self-start"
                disabled={form.processing || form.data.roster.trim() === ''}
            >
                {form.processing && <Spinner />}
                {t('cohorts.roster.add')}
            </Button>
        </form>
    );
}

function EditCohort({ cohort }: { cohort: Cohort }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: cohort.summary.name,
        starts_on: cohort.summary.startsOn ?? '',
        ends_on: cohort.summary.endsOn ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(org.cohorts.update.url(cohort.summary.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="min-h-11">
                    <Pencil aria-hidden className="size-4" />
                    {t('common.edit')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('cohorts.edit_title')}</DialogTitle>
                    <DialogDescription>{t('cohorts.add_hint')}</DialogDescription>
                </DialogHeader>
                <CohortFields form={form} id="edit-cohort" onSubmit={submit} />
            </DialogContent>
        </Dialog>
    );
}
