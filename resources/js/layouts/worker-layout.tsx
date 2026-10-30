import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, Menu } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { LanguageSwitcher } from '@/components/bs/language-switcher';
import { LiveUpdates } from '@/components/bs/live-updates';
import { Logo } from '@/components/bs/logo';
import { NotificationBell } from '@/components/bs/notification-bell';
import { PilotBanner } from '@/components/bs/pilot-banner';
import { SkipLink } from '@/components/bs/skip-link';
import { ThemeToggle } from '@/components/bs/theme-toggle';
import { TranslationPendingBanner } from '@/components/bs/translation-pending-banner';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavLink } from '@/layouts/navigation';
import { navBadge, workerPrimaryNav, workerSecondaryNav } from '@/layouts/navigation';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { dashboard, logout } from '@/routes';

/**
 * Mobile-first worker shell: bottom tab bar + "More" sheet on phones, side navigation from `lg`.
 */
export default function WorkerLayout({ children }: { children: ReactNode }) {
    const { t } = useTranslation();
    const { auth, unread } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const [moreOpen, setMoreOpen] = useState(false);
    const isActive = (item: NavLink) => item.href !== undefined && isCurrentOrParentUrl(item.href);
    const moreActive = workerSecondaryNav.some(isActive);
    const moreBadge = workerSecondaryNav.reduce((sum, item) => sum + navBadge(item.key, unread), 0);

    return (
        <div className="min-h-svh bg-bg lg:grid lg:grid-cols-[248px_minmax(0,1fr)]">
            <SkipLink />
            <LiveUpdates />

            <aside className="sticky top-0 hidden h-svh flex-col gap-6 border-e border-line bg-surface p-4 lg:flex">
                <div className="flex items-center justify-between gap-2">
                    <Link href={dashboard()} aria-label={t('app.home')}>
                        <Logo />
                    </Link>
                    <NotificationBell />
                </div>
                <nav aria-label={t('nav.main')} className="flex flex-1 flex-col gap-0.5">
                    {workerPrimaryNav.map((item) => (
                        <SideLink
                            key={item.key}
                            item={item}
                            active={isActive(item)}
                            count={navBadge(item.key, unread)}
                        />
                    ))}
                    <hr className="my-2 border-line" />
                    {workerSecondaryNav.map((item) => (
                        <SideLink
                            key={item.key}
                            item={item}
                            active={isActive(item)}
                            count={navBadge(item.key, unread)}
                        />
                    ))}
                </nav>
                <div className="flex flex-col gap-2 border-t border-line pt-4">
                    {auth.user && (
                        <p className="truncate text-sm font-semibold">{auth.user.name}</p>
                    )}
                    <div className="flex flex-wrap gap-1.5">
                        <LanguageSwitcher />
                        <ThemeToggle />
                    </div>
                    {auth.user && <SignOutLink className="-ms-3 me-auto" />}
                </div>
            </aside>

            <div className="flex min-w-0 flex-col">
                <PilotBanner />
                <header className="sticky top-0 z-30 flex min-h-14 items-center gap-2 border-b border-line bg-surface/95 px-3 backdrop-blur lg:hidden">
                    <Link href={dashboard()} aria-label={t('app.home')} className="me-auto">
                        <Logo />
                    </Link>
                    <NotificationBell />
                    <LanguageSwitcher />
                </header>
                <TranslationPendingBanner />
                <main
                    id="main"
                    tabIndex={-1}
                    className="mx-auto w-full max-w-3xl min-w-0 flex-1 py-5 gutter pb-28 focus:outline-none lg:pb-8"
                >
                    {children}
                </main>
            </div>

            <nav
                aria-label={t('nav.main')}
                className="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-line bg-surface pb-safe lg:hidden"
            >
                {workerPrimaryNav.map((item) => (
                    <TabLink key={item.key} item={item} active={isActive(item)} />
                ))}
                <button
                    type="button"
                    onClick={() => setMoreOpen(true)}
                    aria-expanded={moreOpen}
                    className={cn(
                        'flex min-h-14 flex-col items-center justify-center gap-0.5 px-1 text-[10.5px] font-semibold',
                        moreActive ? 'text-brand' : 'text-muted',
                    )}
                >
                    <span className="relative">
                        <Menu aria-hidden className="size-5" />
                        {moreBadge > 0 && (
                            <span
                                aria-hidden
                                className="absolute -end-1 -top-1 size-2.5 rounded-full bg-bad ring-2 ring-surface"
                            />
                        )}
                    </span>
                    <span className="max-w-full truncate">{t('common.more')}</span>
                    {moreBadge > 0 && (
                        <span className="sr-only">{t('nav.unread', { count: moreBadge })}</span>
                    )}
                </button>
            </nav>

            <Sheet open={moreOpen} onOpenChange={setMoreOpen}>
                <SheetContent side="bottom" className="rounded-t-2xl bg-surface pb-safe">
                    <SheetHeader className="pe-14">
                        <SheetTitle>{t('common.more')}</SheetTitle>
                    </SheetHeader>
                    <nav aria-label={t('common.more')} className="flex flex-col gap-0.5 px-2">
                        {workerSecondaryNav.map((item) => (
                            <SideLink
                                key={item.key}
                                item={item}
                                active={isActive(item)}
                                count={navBadge(item.key, unread)}
                                onNavigate={() => setMoreOpen(false)}
                            />
                        ))}
                    </nav>
                    <div className="flex flex-wrap items-center gap-2 border-t border-line px-4 pt-3 pb-4">
                        <LanguageSwitcher variant="full" />
                        <ThemeToggle />
                        {auth.user && <SignOutLink className="ms-auto" />}
                    </div>
                </SheetContent>
            </Sheet>
        </div>
    );
}

function SideLink({
    item,
    active,
    count = 0,
    onNavigate,
}: {
    item: NavLink;
    active: boolean;
    count?: number;
    onNavigate?: () => void;
}) {
    const { t } = useTranslation();
    const classes = 'flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm';

    if (!item.href) {
        return (
            <span aria-disabled className={cn(classes, 'cursor-default text-muted')}>
                <item.icon aria-hidden className="size-4.5 shrink-0" />
                <span className="flex-1">{t(item.label)}</span>
                <span className="text-[10px] font-semibold uppercase">{t('common.soon')}</span>
            </span>
        );
    }

    return (
        <Link
            href={item.href}
            onClick={onNavigate}
            aria-current={active ? 'page' : undefined}
            className={cn(
                classes,
                active
                    ? 'bg-brand-soft font-semibold text-brand'
                    : 'text-muted hover:bg-surface-2 hover:text-ink',
            )}
        >
            <item.icon aria-hidden className="size-4.5 shrink-0" />
            <span className="flex-1">{t(item.label)}</span>
            {count > 0 && <CountBadge count={count} />}
        </Link>
    );
}

function TabLink({ item, active }: { item: NavLink; active: boolean }) {
    const { t } = useTranslation();
    const content = (
        <>
            <item.icon aria-hidden className="size-5" />
            <span className="max-w-full truncate">{t(item.label)}</span>
        </>
    );
    const classes =
        'flex min-h-14 flex-col items-center justify-center gap-0.5 px-1 text-[10.5px] font-semibold';

    if (!item.href) {
        return (
            <span aria-disabled title={t('common.soon')} className={cn(classes, 'text-muted/60')}>
                {content}
            </span>
        );
    }

    return (
        <Link
            href={item.href}
            aria-current={active ? 'page' : undefined}
            className={cn(classes, active ? 'text-brand' : 'text-muted')}
        >
            {content}
        </Link>
    );
}

function CountBadge({ count }: { count: number }) {
    const { t, formatNumber } = useTranslation();

    return (
        <span className="rounded-full bg-bad px-1.5 text-[11px] leading-5 font-bold text-on-brand">
            <span aria-hidden>{formatNumber(count)}</span>
            <span className="sr-only">{t('nav.unread', { count })}</span>
        </span>
    );
}

/** In the "More" sheet on phones and at the foot of the side navigation from `lg`. */
function SignOutLink({ className }: { className?: string }) {
    const { t } = useTranslation();

    return (
        <Link
            href={logout()}
            as="button"
            onClick={() => router.flushAll()}
            className={cn(
                'inline-flex min-h-11 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-bad hover:bg-bad-soft',
                className,
            )}
        >
            <LogOut aria-hidden className="size-4 rtl:-scale-x-100" />
            {t('common.sign_out')}
        </Link>
    );
}
