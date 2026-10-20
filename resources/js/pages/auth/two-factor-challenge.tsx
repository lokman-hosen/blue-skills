import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { useTranslation } from '@/lib/i18n';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const { t } = useTranslation();
    const [showRecoveryInput, setShowRecoveryInput] = useState(false);
    const [code, setCode] = useState('');

    setLayoutProps(
        showRecoveryInput
            ? {
                  title: t('auth.two_factor.recovery_title'),
                  description: t('auth.two_factor.recovery_body'),
              }
            : {
                  title: t('auth.two_factor.code_title'),
                  description: t('auth.two_factor.code_body'),
              },
    );

    return (
        <>
            <Head title={t('auth.two_factor.head')} />
            <Form
                {...store.form()}
                className="flex flex-col gap-5"
                resetOnError
                resetOnSuccess={!showRecoveryInput}
            >
                {({ errors, processing, clearErrors }) => (
                    <>
                        {showRecoveryInput ? (
                            <div className="flex flex-col gap-1.5">
                                <Input
                                    name="recovery_code"
                                    type="text"
                                    autoFocus
                                    required
                                    autoComplete="one-time-code"
                                    aria-label={t('auth.two_factor.recovery_title')}
                                    className="h-11"
                                />
                                <InputError message={errors.recovery_code} />
                            </div>
                        ) : (
                            <div className="flex flex-col items-center gap-3">
                                <div dir="ltr">
                                    <InputOTP
                                        name="code"
                                        maxLength={OTP_MAX_LENGTH}
                                        value={code}
                                        onChange={setCode}
                                        disabled={processing}
                                        pattern={REGEXP_ONLY_DIGITS}
                                        inputMode="numeric"
                                        autoComplete="one-time-code"
                                        autoFocus
                                        aria-label={t('auth.two_factor.code_title')}
                                    >
                                        <InputOTPGroup>
                                            {Array.from({ length: OTP_MAX_LENGTH }, (_, index) => (
                                                <InputOTPSlot
                                                    key={index}
                                                    index={index}
                                                    className="size-11 text-lg"
                                                />
                                            ))}
                                        </InputOTPGroup>
                                    </InputOTP>
                                </div>
                                <InputError message={errors.code} className="text-center" />
                            </div>
                        )}

                        <Button type="submit" className="min-h-11 w-full" disabled={processing}>
                            {processing && <Spinner />}
                            {t('onboarding.org.submit')}
                        </Button>

                        <button
                            type="button"
                            className="inline-flex min-h-11 items-center justify-center text-sm font-semibold text-brand hover:underline"
                            onClick={() => {
                                setShowRecoveryInput(!showRecoveryInput);
                                clearErrors();
                                setCode('');
                            }}
                        >
                            {showRecoveryInput
                                ? t('auth.two_factor.use_code')
                                : t('auth.two_factor.use_recovery')}
                        </button>
                    </>
                )}
            </Form>
        </>
    );
}
