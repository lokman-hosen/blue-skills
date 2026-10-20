import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { MailX } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { login, register } from '@/routes';

type Props = {
    invitation: App.Domain.Organization.Data.TeamInvitationData | null;
};

/** Landing page for the link in an invitation email. */
export default function InvitationLanding({ invitation }: Props) {
    const { t, formatDate } = useTranslation();

    setLayoutProps({
        title: invitation
            ? t('team.join_title', { organization: invitation.organization.displayName })
            : t('team.invalid_title'),
        description: invitation
            ? t('team.join_body', {
                  inviter: invitation.invitedBy ?? 'BLUESKILLS',
                  role: t(`role.${invitation.role}`),
              })
            : '',
    });

    if (!invitation) {
        return (
            <>
                <Head title={t('team.invalid_title')} />
                <EmptyState
                    icon={MailX}
                    title={t('team.invalid_title')}
                    description={t('team.invalid_body')}
                />
            </>
        );
    }

    return (
        <>
            <Head
                title={t('team.join_title', { organization: invitation.organization.displayName })}
            />
            <div className="flex flex-col gap-3">
                <p className="text-center text-sm text-muted">
                    {t('team.join_for', { email: invitation.email ?? '' })} ·{' '}
                    {t('team.expires', { date: formatDate(invitation.expiresAt) })}
                </p>
                <Button asChild className="min-h-12">
                    <Link href={register({ query: { email: invitation.email ?? '' } })}>
                        {t('team.create_account')}
                    </Link>
                </Button>
                <Button asChild variant="outline" className="min-h-12">
                    <Link href={login()}>{t('team.sign_in_to_accept')}</Link>
                </Button>
            </div>
        </>
    );
}
