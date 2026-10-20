import { Check, Monitor, Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const options = [
    { value: 'light', icon: Sun },
    { value: 'dark', icon: Moon },
    { value: 'system', icon: Monitor },
] as const satisfies { value: Appearance; icon: typeof Sun }[];

export function ThemeToggle({ className }: { className?: string }) {
    const { t } = useTranslation();
    const { appearance, updateAppearance } = useAppearance();
    // Use the stored setting, not the resolved theme: the server cannot know the OS theme,
    // so a resolved-theme icon would not match during hydration.
    const Icon = options.find((option) => option.value === appearance)?.icon ?? Monitor;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className={cn('min-h-11 min-w-11 sm:min-h-9', className)}
                    aria-label={`${t('theme.label')}: ${t(`theme.${appearance}`)}`}
                >
                    <Icon aria-hidden />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {options.map(({ value, icon: OptionIcon }) => (
                    <DropdownMenuItem
                        key={value}
                        onSelect={() => updateAppearance(value)}
                        className="min-h-11 gap-2"
                    >
                        <OptionIcon aria-hidden />
                        {t(`theme.${value}`)}
                        {appearance === value && (
                            <Check aria-hidden className="ms-auto size-4 text-brand" />
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
