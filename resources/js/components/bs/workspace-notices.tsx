import { Link, usePage } from '@inertiajs/react';
import { ShieldAlert, ShieldCheck, X } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from '@/lib/i18n';
import appeals from '@/routes/appeals';
import { edit as verificationEvidence } from '@/routes/organizations/verification';
import { edit as securitySettings } from '@/routes/security';

const TWO_FACTOR_DISMISSED = 'blueskills.2fa-prompt-dismissed';

/**
 * Organization workspace notices: verification state (D-036) and the 2FA prompt (D-035).
 */
export function WorkspaceNotices() {
    const { t } = useTranslation();
    const { auth, currentOrganization, organizationAbilities } = usePage().props;
    const [twoFactorDismissed, setTwoFactorDismissed] = useState(() => {
        try {
            return sessionStorage.getItem(TWO_FACTOR_DISMISSED) === '1';
        } catch {
            return false;
        }
    });

    const verification = currentOrganization?.verification.value;
    const showVerification = currentOrganization !== null && verification !== 'verified';
    const showTwoFactor = auth.user !== null && !auth.user.twoFactorEnabled && !twoFactorDismissed;

    if (!showVerification && !showTwoFactor) {
        return null;
    }

    return (
        <div className="mb-5 flex flex-col gap-2">
            {showVerification && (
                <div
                    role="status"
                    className="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-warn/30 bg-warn-soft px-4 py-3 text-sm text-ink"
                >
                    <ShieldAlert aria-hidden className="size-5 shrink-0 text-warn" />
                    <p className="min-w-0 flex-1">
                        {t(
                            `organization.notice.${verification}`,
                            {},
                            t('organization.notice.pending'),
                        )}
                    </p>
                    {verification === 'suspended' ? (
                        <Link
                            href={appeals.index()}
                            className="inline-flex min-h-11 items-center font-semibold text-brand hover:underline"
                        >
                            {t('organization.notice.appeal')}
                        </Link>
                    ) : (
                        organizationAbilities.update && (
                            <Link
                                href={verificationEvidence()}
                                className="inline-flex min-h-11 items-center font-semibold text-brand hover:underline"
                            >
                                {t('organization.notice.add_documents')}
                            </Link>
                        )
                    )}
                </div>
            )}
            {showTwoFactor && (
                <div
                    role="status"
                    className="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-brand/30 bg-brand-soft px-4 py-3 text-sm text-ink"
                >
                    <ShieldCheck aria-hidden className="size-5 shrink-0 text-brand" />
                    <p className="min-w-0 flex-1">{t('security.two_factor.prompt')}</p>
                    <Link
                        href={securitySettings()}
                        className="inline-flex min-h-11 items-center font-semibold text-brand hover:underline"
                    >
                        {t('security.two_factor.enable')}
                    </Link>
                    <button
                        type="button"
                        onClick={() => {
                            setTwoFactorDismissed(true);

                            try {
                                sessionStorage.setItem(TWO_FACTOR_DISMISSED, '1');
                            } catch {
                                // Storage can be unavailable (private mode); the prompt just returns next page.
                            }
                        }}
                        className="grid size-11 place-items-center rounded-lg text-muted hover:bg-surface hover:text-ink"
                        aria-label={t('common.close')}
                    >
                        <X aria-hidden className="size-4" />
                    </button>
                </div>
            )}
        </div>
    );
}
