import { Form, Head, Link, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import { send } from '@/routes/verification';
import worker from '@/routes/worker';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    canEditEmail: boolean;
    canDeleteAccount: boolean;
};

export default function Profile({
    mustVerifyEmail,
    status,
    canEditEmail,
    canDeleteAccount,
}: Props) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user;

    return (
        <>
            <Head title={t('settings.profile.title')} />

            <div className="flex flex-col gap-6">
                <Heading
                    variant="small"
                    title={t('settings.profile.title')}
                    description={t(
                        canEditEmail ? 'settings.profile.body' : 'settings.profile.body_worker',
                    )}
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{ preserveScroll: true }}
                    className="flex flex-col gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="name">{t('auth.worker.name')}</Label>
                                <Input
                                    id="name"
                                    className="h-11"
                                    defaultValue={user?.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    aria-invalid={!!errors.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            {canEditEmail && (
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="email">{t('auth.email')}</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        className="h-11"
                                        defaultValue={user?.email ?? ''}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        aria-invalid={!!errors.email}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            )}

                            {mustVerifyEmail && user?.emailVerifiedAt === null && (
                                <div className="flex flex-col gap-2 text-sm">
                                    <p className="text-muted">
                                        {t('settings.profile.unverified')}{' '}
                                        <Link
                                            href={send()}
                                            as="button"
                                            className="font-semibold text-brand underline-offset-4 hover:underline"
                                        >
                                            {t('auth.verify_email.resend')}
                                        </Link>
                                    </p>
                                    {status === 'verification-link-sent' && (
                                        <p role="status" className="font-medium text-ok">
                                            {t('auth.verify_email.sent')}
                                        </p>
                                    )}
                                </div>
                            )}

                            <Button
                                disabled={processing}
                                className="min-h-11 self-start"
                                data-test="update-profile-button"
                            >
                                {t('common.save')}
                            </Button>
                        </>
                    )}
                </Form>
            </div>

            {canDeleteAccount ? (
                <DeleteUser />
            ) : (
                <p className="text-sm text-muted">
                    {t('settings.worker_delete_hint')}{' '}
                    <Link
                        href={worker.privacy.show()}
                        className="font-semibold text-brand underline"
                    >
                        {t('nav.privacy')}
                    </Link>
                </p>
            )}
        </>
    );
}
