import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { MailX } from 'lucide-react';
import StaffInvitationAcceptanceController from '@/actions/App/Http/Controllers/Admin/StaffInvitationAcceptanceController';
import { EmptyState } from '@/components/bs/empty-state';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';

type Props = {
    token: string;
    invitation: App.Domain.Identity.Data.StaffInvitationData | null;
};

/** New staff set their name and password; 2FA setup follows immediately (D-035). */
export default function StaffInvitation({ token, invitation }: Props) {
    const { t } = useTranslation();

    setLayoutProps({
        title: invitation ? t('staff.join_title') : t('team.invalid_title'),
        description: invitation ? t('staff.join_body', { role: t(`role.${invitation.role}`) }) : '',
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
            <Head title={t('staff.join_title')} />
            <Form
                {...StaffInvitationAcceptanceController.store.form(token)}
                resetOnError={['password', 'password_confirmation']}
                className="flex flex-col gap-4"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="email">{t('team.email')}</Label>
                            <Input
                                id="email"
                                value={invitation.email}
                                readOnly
                                disabled
                                className="h-11"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="name">{t('auth.worker.name')}</Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                autoFocus
                                autoComplete="name"
                                className="h-11"
                                aria-invalid={!!errors.name}
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="password">{t('auth.password')}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoComplete="new-password"
                                aria-invalid={!!errors.password}
                            />
                            <InputError message={errors.password} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="password_confirmation">
                                {t('auth.password_confirmation')}
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autoComplete="new-password"
                            />
                        </div>
                        <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                            {t('staff.mail.two_factor')}
                        </p>
                        <Button type="submit" className="min-h-12" disabled={processing}>
                            {processing && <Spinner />}
                            {t('staff.join_submit')}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}
