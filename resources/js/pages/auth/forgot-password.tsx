import { Form, Head, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const { t } = useTranslation();

    setLayoutProps({ title: t('auth.forgot.title'), description: t('auth.forgot.body') });

    return (
        <>
            <Head title={t('auth.forgot.title')} />

            {status && (
                <p
                    role="status"
                    className="mb-4 rounded-lg bg-ok-soft px-3 py-2 text-center text-sm font-medium text-ok"
                >
                    {status}
                </p>
            )}

            <Form {...email.form()} className="flex flex-col gap-5">
                {({ processing, errors }) => (
                    <>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="email">{t('auth.email')}</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                autoFocus
                                className="h-11"
                                aria-invalid={!!errors.email}
                            />
                            <InputError message={errors.email} />
                        </div>
                        <Button
                            className="min-h-11 w-full"
                            disabled={processing}
                            data-test="email-password-reset-link-button"
                        >
                            {processing && <Spinner />}
                            {t('auth.forgot.submit')}
                        </Button>
                    </>
                )}
            </Form>

            <p className="mt-6 text-center text-sm text-muted">
                <TextLink href={login()}>{t('auth.back_to_sign_in')}</TextLink>
            </p>
        </>
    );
}
