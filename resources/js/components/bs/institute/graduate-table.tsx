import { Link, router, useForm } from '@inertiajs/react';
import { Trash2, Users, Video } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
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
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Member = App.Domain.Institute.Data.CohortMemberData;
type IssuedRecord = App.Domain.Institute.Data.IssuedRecordData;

export type GraduateAbilities = { invite: boolean; attest: boolean; assess: boolean };

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/**
 * Graduates with their invitation, SkillShow, training claim, assessment and progress sharing
 * (prototype i-grads). Attest and assess only graduates who accepted (product rule 11).
 */
export function GraduateTable({
    members,
    abilities,
    showCohort = false,
    empty,
}: {
    members: Member[];
    abilities: GraduateAbilities;
    showCohort?: boolean;
    empty?: ReactNode;
}) {
    const canRemove = abilities.invite;
    const { t, formatDate } = useTranslation();

    return (
        <DataTable<Member>
            caption={t('cohorts.graduates')}
            rows={members}
            rowKey={(row) => row.id}
            empty={
                empty ?? (
                    <EmptyState
                        icon={Users}
                        title={t('cohorts.members_empty_title')}
                        description={t('cohorts.members_empty_body')}
                    />
                )
            }
            columns={[
                {
                    key: 'name',
                    header: t('cohorts.column.graduate'),
                    primary: true,
                    cell: (row) => (
                        <span className="font-semibold">
                            {row.name}
                            {row.passportReference && (
                                <span
                                    className="block font-mono text-xs font-normal text-muted"
                                    dir="ltr"
                                >
                                    {row.passportReference}
                                </span>
                            )}
                            {row.rosterPhone && (
                                <span
                                    className="block font-mono text-xs font-normal text-muted"
                                    dir="ltr"
                                >
                                    {row.rosterPhone}
                                </span>
                            )}
                        </span>
                    ),
                },
                ...(showCohort
                    ? [
                          {
                              key: 'cohort',
                              header: t('cohorts.column.cohort'),
                              cell: (row: Member) => (
                                  <Link
                                      href={org.cohorts.show(row.cohortId)}
                                      className="text-brand hover:underline"
                                  >
                                      {row.cohortName}
                                      <span className="block text-xs text-muted">
                                          {row.courseTitle}
                                      </span>
                                  </Link>
                              ),
                          },
                      ]
                    : []),
                {
                    key: 'status',
                    header: t('cohorts.column.invitation'),
                    cell: (row) => (
                        <span className="flex flex-col items-start gap-1">
                            <StatusChip status={row.status} />
                            {row.respondedAt && (
                                <span className="text-xs text-muted">
                                    {formatDate(row.respondedAt)}
                                </span>
                            )}
                        </span>
                    ),
                },
                {
                    key: 'skillshow',
                    header: t('nav.skillshow'),
                    cell: (row) =>
                        row.hasSkillShow ? (
                            <span className="inline-flex items-center gap-1 text-sm">
                                <Video aria-hidden className="size-4 text-brand" />
                                {t('common.yes')}
                            </span>
                        ) : (
                            <span className="text-muted">—</span>
                        ),
                },
                {
                    key: 'attestation',
                    header: t('institute.records.attestation'),
                    cell: (row) => (
                        <RecordCell
                            record={row.attestation}
                            kind="attestation"
                            action={
                                abilities.attest &&
                                row.status.value === 'member_accepted' &&
                                (!row.attestation || row.attestation.withdrawn) ? (
                                    <AttestDialog member={row} />
                                ) : null
                            }
                            canWithdraw={abilities.attest}
                        />
                    ),
                },
                {
                    key: 'assessment',
                    header: t('institute.records.assessment'),
                    cell: (row) => (
                        <RecordCell
                            record={row.assessment}
                            kind="assessment"
                            action={
                                abilities.assess && row.status.value === 'member_accepted' ? (
                                    <AssessDialog member={row} />
                                ) : null
                            }
                            canWithdraw={abilities.assess}
                        />
                    ),
                },
                {
                    key: 'sharing',
                    header: t('cohorts.column.progress'),
                    cell: (row) =>
                        row.sharesProgress ? (
                            t('cohorts.shares_progress')
                        ) : (
                            <span className="text-muted">{t('cohorts.not_shared')}</span>
                        ),
                },
                ...(canRemove
                    ? [
                          {
                              key: 'remove',
                              header: t('cohorts.column.actions'),
                              cell: (row: Member) =>
                                  row.status.value === 'member_invited' ? (
                                      <Button
                                          variant="ghost"
                                          className="min-h-11 text-bad"
                                          aria-label={t('cohorts.roster.remove', {
                                              name: row.name,
                                          })}
                                          onClick={() =>
                                              router.delete(
                                                  org.cohorts.members.destroy.url([
                                                      row.cohortId,
                                                      row.id,
                                                  ]),
                                                  {
                                                      preserveScroll: true,
                                                  },
                                              )
                                          }
                                      >
                                          <Trash2 aria-hidden className="size-4" />
                                      </Button>
                                  ) : null,
                          },
                      ]
                    : []),
            ]}
        />
    );
}

function RecordCell({
    record,
    kind,
    action,
    canWithdraw,
}: {
    record: IssuedRecord | null;
    kind: 'attestation' | 'assessment';
    action: ReactNode;
    canWithdraw: boolean;
}) {
    const { t, formatDate, formatNumber } = useTranslation();

    if (!record) {
        return action ?? <span className="text-muted">—</span>;
    }

    return (
        <span className="flex flex-col items-start gap-1">
            <StatusChip status={record.status} />
            <span className="text-xs text-muted">
                {formatDate(record.date)}
                {record.score !== null &&
                    ` · ${t('institute.records.score', { score: formatNumber(record.score) })}`}
            </span>
            {record.withdrawn && record.withdrawnReason && (
                <span className="text-xs text-bad">{record.withdrawnReason}</span>
            )}
            {!record.withdrawn && canWithdraw && <WithdrawDialog record={record} kind={kind} />}
            {action}
        </span>
    );
}

function AttestDialog({ member }: { member: Member }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const today = new Date().toISOString().slice(0, 10);
    const ends = member.cohortEndsOn;
    const form = useForm({ completed_on: ends && ends < today ? ends : today, note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.cohorts.members.attest.url([member.cohortId, member.id]), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button className="min-h-11" size="sm">
                    {t('institute.records.attest')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('institute.records.attest_title', { name: member.name })}
                    </DialogTitle>
                    <DialogDescription>
                        {t('institute.records.attest_hint', { course: member.courseTitle })}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5 sm:max-w-56">
                        <Label htmlFor={`attest-date-${member.id}`}>
                            {t('institute.records.completed_on')}
                        </Label>
                        <Input
                            id={`attest-date-${member.id}`}
                            type="date"
                            max={today}
                            className="h-11"
                            value={form.data.completed_on}
                            onChange={(event) => form.setData('completed_on', event.target.value)}
                        />
                        <InputError message={form.errors.completed_on} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`attest-note-${member.id}`}>
                            {t('institute.records.note_label')} ({t('common.optional')})
                        </Label>
                        <textarea
                            id={`attest-note-${member.id}`}
                            rows={2}
                            maxLength={500}
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            className={TEXTAREA}
                        />
                        <InputError
                            message={
                                form.errors.note ??
                                (form.errors as Record<string, string>).attestation
                            }
                        />
                    </div>
                    <Button type="submit" className="min-h-11 self-end" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {t('institute.records.attest')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AssessDialog({ member }: { member: Member }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const today = new Date().toISOString().slice(0, 10);
    const form = useForm({
        title: t('institute.records.default_title', { course: member.courseTitle }),
        result: 'pass',
        score: '',
        assessed_on: today,
        note: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.cohorts.members.assess.url([member.cohortId, member.id]), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="min-h-11" size="sm">
                    {t('institute.records.assess')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('institute.records.assess_title', { name: member.name })}
                    </DialogTitle>
                    <DialogDescription>{t('institute.records.assess_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`assess-title-${member.id}`}>
                            {t('institute.records.test_title')}
                        </Label>
                        <Input
                            id={`assess-title-${member.id}`}
                            className="h-11"
                            maxLength={160}
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                        />
                        <InputError message={form.errors.title} />
                    </div>
                    <fieldset className="flex flex-col gap-1.5">
                        <legend className="text-sm font-medium">
                            {t('institute.records.result')}
                        </legend>
                        <div className="flex flex-wrap gap-2">
                            {(['pass', 'fail'] as const).map((value) => (
                                <label
                                    key={value}
                                    className="flex min-h-11 cursor-pointer items-center gap-2 rounded-xl border border-line px-3 has-[:checked]:border-brand has-[:checked]:bg-brand-soft"
                                >
                                    <input
                                        type="radio"
                                        name={`result-${member.id}`}
                                        value={value}
                                        checked={form.data.result === value}
                                        onChange={() => form.setData('result', value)}
                                        className="size-4 accent-[var(--color-brand)]"
                                    />
                                    {t(`status.assessment_${value}`)}
                                </label>
                            ))}
                        </div>
                        <InputError message={form.errors.result} />
                    </fieldset>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor={`assess-score-${member.id}`}>
                                {t('institute.records.score_label')} ({t('common.optional')})
                            </Label>
                            <Input
                                id={`assess-score-${member.id}`}
                                type="number"
                                inputMode="numeric"
                                min={0}
                                max={100}
                                className="h-11"
                                value={form.data.score}
                                onChange={(event) => form.setData('score', event.target.value)}
                            />
                            <InputError message={form.errors.score} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor={`assess-date-${member.id}`}>
                                {t('institute.records.assessed_on')}
                            </Label>
                            <Input
                                id={`assess-date-${member.id}`}
                                type="date"
                                max={today}
                                className="h-11"
                                value={form.data.assessed_on}
                                onChange={(event) =>
                                    form.setData('assessed_on', event.target.value)
                                }
                            />
                            <InputError message={form.errors.assessed_on} />
                        </div>
                    </div>
                    <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                        {t('institute.records.fail_note')}
                    </p>
                    <Button type="submit" className="min-h-11 self-end" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {t('institute.records.save_result')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function WithdrawDialog({
    record,
    kind,
}: {
    record: IssuedRecord;
    kind: 'attestation' | 'assessment';
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({ reason: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const url =
            kind === 'attestation'
                ? org.attestations.withdraw.url(record.id)
                : org.assessments.withdraw.url(record.id);
        form.post(url, { preserveScroll: true, onSuccess: () => setOpen(false) });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm" className="min-h-11 px-0 text-bad">
                    {t('institute.records.withdraw')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('institute.records.withdraw_title', { title: record.title })}
                    </DialogTitle>
                    <DialogDescription>{t('institute.records.withdraw_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <Label htmlFor={`withdraw-${record.id}`}>
                        {t('institute.records.withdraw_reason')}
                    </Label>
                    <textarea
                        id={`withdraw-${record.id}`}
                        rows={3}
                        maxLength={500}
                        value={form.data.reason}
                        onChange={(event) => form.setData('reason', event.target.value)}
                        className={TEXTAREA}
                    />
                    <InputError message={form.errors.reason} />
                    <Button
                        type="submit"
                        variant="destructive"
                        className="min-h-11 self-end"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        {t('institute.records.withdraw')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
