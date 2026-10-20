import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { LanguageSwitcher } from './language-switcher';
import { Logo } from './logo';
import { SkipLink } from './skip-link';
import { ThemeToggle } from './theme-toggle';
import { TranslationPendingBanner } from './translation-pending-banner';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavGroup } from '@/layouts/navigation';
import { useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';

type WorkspaceShellProps = {
    nav: NavGroup[];
    /** Left of the top bar: org switcher + verification badge, or the staff badge. */
    context: ReactNode;
    children: ReactNode;
};

/**
 * Desktop workspace for organizations and staff: collapsible icon sidebar on `md`+, a drawer
 * on phones. The sidebar sits on the reading-start side (right in RTL).
 */
export function WorkspaceShell({ nav, context, children }: WorkspaceShellProps) {
    const { t, dir } = useTranslation();
    const { sidebarOpen, auth } = usePage().props;
    const permissions = auth.user?.staffPermissions ?? [];
    const visibleNav = nav
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) => !item.permission || permissions.includes(item.permission),
            ),
        }))
        .filter((group) => group.items.length > 0);
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarProvider defaultOpen={sidebarOpen}>
            <SkipLink />
            <Sidebar side={dir === 'rtl' ? 'right' : 'left'} collapsible="icon">
                <SidebarHeader>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" asChild>
                                <Link href={dashboard()} aria-label={t('app.home')}>
                                    <Logo />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>
                <SidebarContent>
                    {visibleNav.map((group, index) => (
                        <SidebarGroup key={index}>
                            <SidebarMenu>
                                {group.items.map((item) => {
                                    const label = t(item.label);

                                    return (
                                        <SidebarMenuItem key={item.key}>
                                            {item.href ? (
                                                <SidebarMenuButton
                                                    asChild
                                                    tooltip={label}
                                                    isActive={
                                                        !item.external &&
                                                        isCurrentOrParentUrl(item.href)
                                                    }
                                                    className="min-h-10"
                                                >
                                                    {item.external ? (
                                                        <a
                                                            href={
                                                                typeof item.href === 'string'
                                                                    ? item.href
                                                                    : item.href.url
                                                            }
                                                        >
                                                            <item.icon aria-hidden />
                                                            <span>{label}</span>
                                                        </a>
                                                    ) : (
                                                        <Link href={item.href} prefetch>
                                                            <item.icon aria-hidden />
                                                            <span>{label}</span>
                                                        </Link>
                                                    )}
                                                </SidebarMenuButton>
                                            ) : (
                                                <>
                                                    <SidebarMenuButton
                                                        aria-disabled
                                                        tooltip={`${label} · ${t('common.soon')}`}
                                                        className="min-h-10 cursor-default text-muted hover:bg-transparent hover:text-muted"
                                                    >
                                                        <item.icon aria-hidden />
                                                        <span>{label}</span>
                                                    </SidebarMenuButton>
                                                    <SidebarMenuBadge className="text-[10px] text-muted">
                                                        {t('common.soon')}
                                                    </SidebarMenuBadge>
                                                </>
                                            )}
                                        </SidebarMenuItem>
                                    );
                                })}
                            </SidebarMenu>
                        </SidebarGroup>
                    ))}
                </SidebarContent>
                <SidebarFooter>
                    <NavUser />
                </SidebarFooter>
            </Sidebar>
            <SidebarInset className="min-w-0 bg-bg">
                <header className="sticky top-0 z-30 flex min-h-14 flex-wrap items-center gap-x-2 gap-y-1 border-b border-line bg-surface/95 px-3 py-1.5 backdrop-blur">
                    <SidebarTrigger className="size-11 md:size-9" aria-label={t('common.menu')} />
                    <div className="min-w-0 flex-1">{context}</div>
                    <div className="flex items-center gap-1.5">
                        <LanguageSwitcher />
                        <ThemeToggle />
                    </div>
                </header>
                <TranslationPendingBanner />
                <main
                    id="main"
                    tabIndex={-1}
                    className="mx-auto w-full max-w-[1400px] min-w-0 py-6 gutter focus:outline-none"
                >
                    {children}
                </main>
            </SidebarInset>
        </SidebarProvider>
    );
}
