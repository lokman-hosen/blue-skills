import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { LanguageSwitcher } from '@/components/bs/language-switcher';
import { Logo } from '@/components/bs/logo';
import { SkipLink } from '@/components/bs/skip-link';
import { ThemeToggle } from '@/components/bs/theme-toggle';
import { TranslationPendingBanner } from '@/components/bs/translation-pending-banner';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

type AuthLayoutProps = {
    title?: string;
    description?: string;
    /** Wider card for multi-field onboarding forms. */
    wide?: boolean;
    children: ReactNode;
};

export default function AuthLayout({
    title = '',
    description = '',
    wide = false,
    children,
}: AuthLayoutProps) {
    const { t } = useTranslation();

    return (
        <div className="flex min-h-svh flex-col bg-bg">
            <SkipLink />
            <header className="flex min-h-14 items-center justify-between gap-2 py-2 gutter">
                <Link href={home()} aria-label={t('app.home')}>
                    <Logo />
                </Link>
                <div className="flex items-center gap-1.5">
                    <LanguageSwitcher />
                    <ThemeToggle />
                </div>
            </header>
            <TranslationPendingBanner />
            <main
                id="main"
                tabIndex={-1}
                className="flex flex-1 items-start justify-center py-8 gutter focus:outline-none sm:items-center"
            >
                <div
                    className={cn(
                        'w-full rounded-2xl border border-line bg-surface p-6 shadow-sm sm:p-8',
                        wide ? 'max-w-2xl' : 'max-w-md',
                    )}
                >
                    {(title || description) && (
                        <div className="mb-6 flex flex-col gap-1.5 text-center">
                            {title && <h1 className="font-display text-2xl font-bold">{title}</h1>}
                            {description && <p className="text-sm text-muted">{description}</p>}
                        </div>
                    )}
                    {children}
                </div>
            </main>
        </div>
    );
}
