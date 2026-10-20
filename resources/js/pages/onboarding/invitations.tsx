import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import InvitationController from '@/actions/App/Http/Controllers/Organization/InvitationController';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import organizations from '@/routes/organizations';

type Props = {
    invitations: App.Domain.Organization.Data.TeamInvitationData[];
};

/** Pending invitations for the signed-in user's email address. */
export default function PendingInvitations({ invitations }: Props) {
    const { t, formatDate } = useTranslation();

    setLayoutProps({
        title: t('team.pending_mine_title'),
        description: t('team.pending_mine_body'),
        wide: true,
    });

    return (
        <>
            <Head title={t('team.pending_mine_title')} />
            <div className="flex flex-col gap-4">
                {invitations.length === 0 && (
                    <p className="text-center text-sm text-muted">{t('team.pending_none')}</p>
                )}
                <ul className="flex flex-col gap-3">
                    {invitations.map((invitation) => (
                        <li
                            key={invitation.id}
                            className="flex flex-wrap items-center gap-3 rounded-xl border border-line p-4"
                        >
                            <span className="grid size-10 shrink-0 place-items-center rounded-lg bg-surface-2 text-brand">
                                <Building2 aria-hidden className="size-5" />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block font-semibold">
                                    {invitation.organization.displayName}
                                </span>
                                <span className="block text-xs text-muted">
                                    {t(`role.${invitation.role}`)} ·{' '}
                                    {t('team.expires', { date: formatDate(invitation.expiresAt) })}
                                </span>
                            </span>
                            <StatusChip status={invitation.organization.verification} />
                            <Button
                                className="min-h-11"
                                onClick={() =>
                                    router.post(InvitationController.accept.url(invitation.id))
                                }
                            >
                                {t('team.accept')}
                            </Button>
                        </li>
                    ))}
                </ul>
                <p className="text-center text-sm text-muted">
                    <Link
                        href={organizations.create()}
                        className="font-semibold text-brand hover:underline"
                    >
                        {t('team.register_instead')}
                    </Link>
                </p>
            </div>
        </>
    );
}
