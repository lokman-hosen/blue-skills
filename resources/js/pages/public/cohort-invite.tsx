import { Head, Link, router } from '@inertiajs/react';
import { BadgeCheck, Check, EyeOff, GraduationCap, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import cohortInvite from '@/routes/cohort-invite';
import worker from '@/routes/worker';

type Props = {
    invite: App.Domain.Institute.Data.CohortInviteData;
    viewer: 'guest' | 'worker' | 'member' | 'organization';
};

/**
 * A training institute's invitation (D-082). Nothing reaches the institute until the worker
 * accepts; they choose whether it may follow their applications (product rule 11).
 */
export default function CohortInvite({ invite, viewer }: Props) {
    const { t, formatDate } = useTranslation();
    const [shares, setShares] = useState(false);
    const [processing, setProcessing] = useState<'accept' | 'decline' | null>(null);

    const answer = (choice: 'accept' | 'decline') =>
        router.post(
            choice === 'accept'
                ? cohortInvite.accept.url(invite.code)
                : cohortInvite.decline.url(invite.code),
            choice === 'accept' ? { shares_progress: shares } : {},
            { onStart: () => setProcessing(choice), onFinish: () => setProcessing(null) },
        );

    return (
        <>
            <Head title={t('cohorts.invite.page_title', { institute: invite.institute })} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-5 px-4 py-8">
                <div className="flex flex-col gap-2">
                    <p className="inline-flex items-center gap-1.5 eyebrow text-muted">
                        <GraduationCap aria-hidden className="size-4" />
                        {t('cohorts.invite.eyebrow')}
                    </p>
                    <h1 className="font-display text-2xl font-bold sm:text-3xl">
                        {t('cohorts.invite.heading', { institute: invite.institute })}
                    </h1>
                    {invite.instituteVerified && (
                        <p className="inline-flex items-center gap-1.5 text-sm font-semibold text-ok">
                            <BadgeCheck aria-hidden className="size-4" />
                            {t('cohorts.invite.verified')}
                        </p>
                    )}
                </div>

                <section className="flex flex-col gap-1 rounded-2xl border border-line bg-surface p-5">
                    <p className="text-lg font-semibold">{invite.course}</p>
                    <p className="text-sm text-muted">
                        {[
                            invite.cohort,
                            invite.occupation,
                            invite.startsOn &&
                                invite.endsOn &&
                                t('cohorts.dates', {
                                    start: formatDate(invite.startsOn),
                                    end: formatDate(invite.endsOn),
                                }),
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                </section>

                <section className="grid gap-3 sm:grid-cols-2">
                    <div className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4">
                        <h2 className="text-sm font-semibold">
                            {t('cohorts.invite.sees_title', { institute: invite.institute })}
                        </h2>
                        <ul className="flex flex-col gap-1.5 text-sm">
                            {['name', 'skillshow', 'evidence'].map((key) => (
                                <li key={key} className="flex items-start gap-2">
                                    <Check aria-hidden className="mt-0.5 size-4 shrink-0 text-ok" />
                                    {t(`cohorts.invite.sees.${key}`)}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4">
                        <h2 className="text-sm font-semibold">{t('cohorts.invite.never_title')}</h2>
                        <ul className="flex flex-col gap-1.5 text-sm">
                            {['edit', 'documents', 'phone'].map((key) => (
                                <li key={key} className="flex items-start gap-2">
                                    <X aria-hidden className="mt-0.5 size-4 shrink-0 text-bad" />
                                    {t(`cohorts.invite.never.${key}`)}
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>

                {!invite.open ? (
                    <p className="rounded-xl bg-surface-2 px-4 py-3 text-sm">
                        {t('cohorts.invite.closed')}
                    </p>
                ) : viewer === 'guest' ? (
                    <div className="flex flex-col gap-2">
                        <Button asChild className="min-h-12">
                            <Link href={worker.join()}>{t('cohorts.invite.join')}</Link>
                        </Button>
                        <p className="text-center text-xs text-muted">
                            {t('cohorts.invite.join_hint')}
                        </p>
                    </div>
                ) : viewer === 'member' ? (
                    <div className="flex flex-col gap-2 rounded-xl bg-ok-soft px-4 py-3 text-sm">
                        <p className="font-semibold">{t('cohorts.invite.already')}</p>
                        <Link
                            href={worker.privacy.show()}
                            className="font-semibold text-brand underline-offset-4 hover:underline"
                        >
                            {t('cohorts.invite.manage')}
                        </Link>
                    </div>
                ) : viewer === 'organization' ? (
                    <p className="rounded-xl bg-surface-2 px-4 py-3 text-sm">
                        {t('cohorts.invite.workers_only')}
                    </p>
                ) : (
                    <section className="flex flex-col gap-4 rounded-2xl border-2 border-brand/40 bg-surface p-5">
                        <div className="flex items-start gap-3">
                            <Checkbox
                                id="shares-progress"
                                checked={shares}
                                onCheckedChange={(checked) => setShares(checked === true)}
                                className="mt-0.5"
                            />
                            <Label
                                htmlFor="shares-progress"
                                className="flex flex-col items-start gap-0.5 font-normal"
                            >
                                <span className="font-semibold">
                                    {t('cohorts.invite.share_progress', {
                                        institute: invite.institute,
                                    })}
                                </span>
                                <span className="text-xs text-muted">
                                    {t('cohorts.invite.share_progress_hint')}
                                </span>
                            </Label>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                className="min-h-12 flex-1"
                                disabled={processing !== null}
                                onClick={() => answer('accept')}
                            >
                                {processing === 'accept' ? (
                                    <Spinner />
                                ) : (
                                    <Check aria-hidden className="size-4" />
                                )}
                                {t('cohorts.invite.accept')}
                            </Button>
                            <Button
                                variant="outline"
                                className="min-h-12"
                                disabled={processing !== null}
                                onClick={() => answer('decline')}
                            >
                                {processing === 'decline' ? (
                                    <Spinner />
                                ) : (
                                    <EyeOff aria-hidden className="size-4" />
                                )}
                                {t('cohorts.invite.decline')}
                            </Button>
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}
