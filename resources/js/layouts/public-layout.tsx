import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { BriefcaseBusiness, Info, Search, ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import { LanguageSwitcher } from '@/components/bs/language-switcher';
import { Logo } from '@/components/bs/logo';
import { SkipLink } from '@/components/bs/skip-link';
import { ThemeToggle } from '@/components/bs/theme-toggle';
import { TranslationPendingBanner } from '@/components/bs/translation-pending-banner';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import type { NavLink } from '@/layouts/navigation';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { dashboard, home, login } from '@/routes';
import jobs from '@/routes/jobs';

// Find Jobs is live (Module 3); the other public screens stay inert until their modules ship.
const publicNav: NavLink[] = [
    { key: 'find_jobs', label: 'nav.find_jobs', icon: Search, href: jobs.index() },
    { key: 'find_talent', label: 'nav.find_talent', icon: BriefcaseBusiness },
    { key: 'how_it_works', label: 'nav.how_it_works', icon: Info },
    { key: 'safety', label: 'nav.safety', icon: ShieldCheck },
];

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const account = auth.user ? (
        <Button asChild className="min-h-11 sm:min-h-9">
            <Link href={dashboard()}>{t('nav.dashboard')}</Link>
        </Button>
    ) : (
        <Button asChild className="min-h-11 sm:min-h-9">
            <Link href={login()}>{t('common.sign_in')}</Link>
        </Button>
    );

    return (
        <div className="flex min-h-svh flex-col bg-bg">
            <SkipLink />
            <header className="sticky top-0 z-30 border-b border-line bg-surface/95 backdrop-blur">
                <div className="mx-auto flex min-h-14 max-w-[1400px] items-center gap-3 py-2 gutter">
                    <Link href={home()} aria-label={t('app.home')} className="me-auto lg:me-4">
                        <Logo />
                    </Link>
                    <nav
                        aria-label={t('nav.main')}
                        className="hidden flex-1 items-center gap-1 lg:flex"
                    >
                        {publicNav.map((item) => (
                            <NavEntry key={item.key} item={item} />
                        ))}
                    </nav>
                    <div className="hidden items-center gap-1.5 sm:flex">
                        <LanguageSwitcher />
                        <ThemeToggle />
                    </div>
                    <div className="hidden sm:block">{account}</div>
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button
                                variant="outline"
                                size="icon"
                                className="size-11 lg:hidden"
                                aria-label={t('common.open_menu')}
                            >
                                <Menu aria-hidden />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="end" className="bg-surface">
                            <SheetHeader className="pe-14">
                                <SheetTitle>{t('common.menu')}</SheetTitle>
                            </SheetHeader>
                            <nav aria-label={t('nav.main')} className="flex flex-col gap-0.5 px-2">
                                {publicNav.map((item) => (
                                    <NavEntry key={item.key} item={item} stacked />
                                ))}
                            </nav>
                            <div className="flex flex-wrap items-center gap-2 border-t border-line p-4">
                                <LanguageSwitcher variant="full" />
                                <ThemeToggle />
                                <div className="w-full">{account}</div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </header>
            <TranslationPendingBanner />
            <main
                id="main"
                tabIndex={-1}
                className="mx-auto w-full max-w-[1400px] min-w-0 flex-1 py-8 gutter focus:outline-none"
            >
                {children}
            </main>
            <footer className="border-t border-line bg-surface">
                <div className="mx-auto flex max-w-[1400px] flex-col gap-3 py-6 gutter sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <Logo />
                        <p className="text-sm text-muted">{t('app.tagline')}</p>
                    </div>
                    <p className="text-xs text-muted">{t('public.footer.note')}</p>
                </div>
            </footer>
        </div>
    );
}

function NavEntry({ item, stacked = false }: { item: NavLink; stacked?: boolean }) {
    const { t } = useTranslation();
    const classes = cn(
        'inline-flex min-h-11 items-center gap-2 rounded-lg px-3 text-sm font-semibold',
        stacked && 'w-full',
    );

    if (!item.href) {
        return (
            <span aria-disabled className={cn(classes, 'cursor-default text-muted')}>
                {stacked && <item.icon aria-hidden className="size-4" />}
                {t(item.label)}
                <span className="sr-only"> · {t('common.soon')}</span>
            </span>
        );
    }

    return (
        <Link href={item.href} className={cn(classes, 'text-ink hover:bg-surface-2')}>
            {stacked && <item.icon aria-hidden className="size-4" />}
            {t(item.label)}
        </Link>
    );
}
