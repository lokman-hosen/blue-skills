import { Form, Head, setLayoutProps } from '@inertiajs/react';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    const { t } = useTranslation();

    setLayoutProps({ title: t('auth.confirm.title'), description: t('auth.confirm.body') });

    return (
        <>
            <Head title={t('auth.confirm.title')} />

            <PasskeyVerify
                routes={{ options: confirmOptions(), submit: confirmStore() }}
                label={t('auth.passkey.confirm')}
                loadingLabel={t('auth.passkey.confirming')}
                separator={t('auth.passkey.or_password')}
            />

            <Form {...store.form()} resetOnSuccess={['password']} className="flex flex-col gap-5">
                {({ processing, errors }) => (
                    <>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="password">{t('auth.password')}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="current-password"
                                autoFocus
                                aria-invalid={!!errors.password}
                            />
                            <InputError message={errors.password} />
                        </div>
                        <Button
                            className="min-h-11 w-full"
                            disabled={processing}
                            data-test="confirm-password-button"
                        >
                            {processing && <Spinner />}
                            {t('auth.confirm.submit')}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}
