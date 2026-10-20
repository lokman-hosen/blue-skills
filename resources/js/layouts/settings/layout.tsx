import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { PageHeader } from '@/components/bs/page-header';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();

    // Workers sign in by phone, so password, 2FA and passkeys do not apply to them.
    const items = [
        { key: 'profile', label: t('settings.nav.profile'), href: edit() },
        ...(auth.user?.type === 'worker'
            ? []
            : [{ key: 'security', label: t('settings.nav.security'), href: editSecurity() }]),
        { key: 'appearance', label: t('settings.nav.appearance'), href: editAppearance() },
    ];

    return (
        <div className="flex flex-col gap-6">
            <PageHeader title={t('nav.settings')} description={t('settings.description')} />

            <div className="flex flex-col gap-6 lg:flex-row lg:gap-12">
                <nav
                    aria-label={t('nav.settings')}
                    className="flex gap-1 overflow-x-auto lg:w-48 lg:flex-col"
                >
                    {items.map((item) => {
                        const active = isCurrentOrParentUrl(item.href);

                        return (
                            <Link
                                key={item.key}
                                href={item.href}
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'inline-flex min-h-11 shrink-0 items-center rounded-lg px-3 text-sm',
                                    active
                                        ? 'bg-brand-soft font-semibold text-brand'
                                        : 'text-muted hover:bg-surface-2 hover:text-ink',
                                )}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>
                <section className="flex max-w-xl min-w-0 flex-1 flex-col gap-12">
                    {children}
                </section>
            </div>
        </div>
    );
}
