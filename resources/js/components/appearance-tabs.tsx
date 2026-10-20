import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();
    const { t } = useTranslation();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: t('theme.light') },
        { value: 'dark', icon: Moon, label: t('theme.dark') },
        { value: 'system', icon: Monitor, label: t('theme.system') },
    ];

    return (
        <div className={cn('inline-flex gap-1 rounded-xl bg-surface-2 p-1', className)} {...props}>
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => updateAppearance(value)}
                    aria-pressed={appearance === value}
                    className={cn(
                        'flex min-h-11 items-center rounded-lg px-3.5 transition-colors',
                        appearance === value
                            ? 'bg-surface font-semibold text-brand shadow-xs'
                            : 'text-muted hover:bg-surface hover:text-ink',
                    )}
                >
                    <Icon className="-ms-1 h-4 w-4" />
                    <span className="ms-1.5 text-sm">{label}</span>
                </button>
            ))}
        </div>
    );
}
