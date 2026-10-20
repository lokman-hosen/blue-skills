import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { Smartphone } from 'lucide-react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import worker from '@/routes/worker';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

/** Email sign-in for organization users and staff. Workers use their phone. */
export default function Login({ status, canResetPassword }: Props) {
    const { t } = useTranslation();

    setLayoutProps({ title: t('auth.login.title'), description: t('auth.login.body') });

    return (
        <>
            <Head title={t('common.sign_in')} />

            {status && (
                <p
                    role="status"
                    className="mb-4 rounded-lg bg-ok-soft px-3 py-2 text-center text-sm font-medium text-ok"
                >
                    {status}
                </p>
            )}

            <PasskeyVerify
                label={t('auth.passkey.sign_in')}
                loadingLabel={t('auth.passkey.signing_in')}
                separator={t('auth.passkey.or_email')}
            />

            <Form {...store.form()} resetOnSuccess={['password']} className="flex flex-col gap-5">
                {({ processing, errors }) => (
                    <>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="email">{t('auth.email')}</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                autoComplete="email"
                                className="h-11"
                                aria-invalid={!!errors.email}
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <div className="flex items-center">
                                <Label htmlFor="password">{t('auth.password')}</Label>
                                {canResetPassword && (
                                    <TextLink href={request()} className="ms-auto text-sm">
                                        {t('auth.forgot.link')}
                                    </TextLink>
                                )}
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoComplete="current-password"
                                aria-invalid={!!errors.password}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex min-h-11 items-center gap-3">
                            <Checkbox id="remember" name="remember" />
                            <Label htmlFor="remember">{t('auth.remember')}</Label>
                        </div>

                        <Button
                            type="submit"
                            className="min-h-11 w-full"
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            {t('common.sign_in')}
                        </Button>

                        <p className="text-center text-sm text-muted">
                            {t('auth.login.no_account')}{' '}
                            <TextLink href={register()}>{t('auth.register.link')}</TextLink>
                        </p>
                    </>
                )}
            </Form>

            <Button asChild variant="outline" className="mt-6 min-h-11 w-full">
                <a href={worker.join().url}>
                    <Smartphone aria-hidden />
                    {t('auth.login.worker_link')}
                </a>
            </Button>
        </>
    );
}
