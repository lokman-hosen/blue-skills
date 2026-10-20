import { Form, Head, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { login } from '@/routes';
import { store } from '@/routes/register';
import worker from '@/routes/worker';

type Props = {
    passwordRules: string;
    email?: string | null;
};

/** Step 1 of organization sign-up: the person's account (D-036). */
export default function Register({ passwordRules, email }: Props) {
    const { t } = useTranslation();

    setLayoutProps({ title: t('auth.register.title'), description: t('auth.register.body') });

    return (
        <>
            <Head title={t('auth.register.title')} />
            <p className="mb-4 text-center eyebrow text-brand">
                {t('onboarding.step', { step: 1, total: 3 })}
            </p>
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="name">{t('auth.worker.name')}</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autoFocus
                                autoComplete="name"
                                name="name"
                                className="h-11"
                                aria-invalid={!!errors.name}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="email">{t('auth.work_email')}</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                autoComplete="email"
                                name="email"
                                defaultValue={email ?? ''}
                                className="h-11"
                                aria-invalid={!!errors.email}
                                aria-describedby="email-hint"
                            />
                            <p id="email-hint" className="text-xs text-muted">
                                {t('auth.register.email_hint')}
                            </p>
                            <InputError message={errors.email} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="password">{t('auth.password')}</Label>
                            <PasswordInput
                                id="password"
                                required
                                autoComplete="new-password"
                                name="password"
                                passwordrules={passwordRules}
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
                                required
                                autoComplete="new-password"
                                name="password_confirmation"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>

                        <Button
                            type="submit"
                            className="min-h-11 w-full"
                            data-test="register-user-button"
                        >
                            {processing && <Spinner />}
                            {t('common.register')}
                        </Button>

                        <p className="text-center text-sm text-muted">
                            {t('auth.register.have_account')}{' '}
                            <TextLink href={login()}>{t('common.sign_in')}</TextLink>
                        </p>
                        <p className="text-center text-sm text-muted">
                            <TextLink href={worker.join()}>{t('auth.login.worker_link')}</TextLink>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}
