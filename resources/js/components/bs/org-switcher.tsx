import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
import { StatusChip } from './status-chip';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { switchMethod } from '@/routes/organizations';

/**
 * Current organization workspace, its verification badge and a switcher for members of
 * several organizations.
 */
export function OrgSwitcher({ className }: { className?: string }) {
    const { t } = useTranslation();
    const { currentOrganization: current, organizations: all } = usePage().props;

    if (!current) {
        return <p className={cn('text-sm text-muted', className)}>{t('org.none')}</p>;
    }

    return (
        <div className={cn('flex min-w-0 flex-wrap items-center gap-2', className)}>
            <DropdownMenu>
                <DropdownMenuTrigger
                    className="flex min-h-11 min-w-0 items-center gap-2 rounded-lg px-2 text-start hover:bg-surface-2"
                    aria-label={`${t('org.switch')}: ${current.displayName}`}
                >
                    <span className="grid size-8 shrink-0 place-items-center rounded-lg bg-surface-2 text-brand">
                        <Building2 aria-hidden className="size-4" />
                    </span>
                    <span className="min-w-0">
                        <span className="block truncate text-sm leading-tight font-semibold">
                            {current.displayName}
                        </span>
                        <span className="block truncate text-[11.5px] text-muted">
                            {t(`org.type.${current.type}`)}
                        </span>
                    </span>
                    <ChevronsUpDown aria-hidden className="size-4 shrink-0 text-muted" />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="min-w-64">
                    <DropdownMenuLabel>{t('org.your_organizations')}</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    {all.map((organization) => (
                        <DropdownMenuItem
                            key={organization.id}
                            className="min-h-11 gap-2"
                            onSelect={() => {
                                if (organization.id !== current.id) {
                                    router.post(switchMethod.url(organization.id));
                                }
                            }}
                        >
                            <span className="min-w-0 flex-1">
                                <span className="block truncate font-semibold">
                                    {organization.displayName}
                                </span>
                                <span className="block text-xs text-muted">
                                    {t(`org.type.${organization.type}`)}
                                </span>
                            </span>
                            {organization.id === current.id && (
                                <Check aria-hidden className="size-4 text-brand" />
                            )}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
            <StatusChip status={current.verification} />
        </div>
    );
}
