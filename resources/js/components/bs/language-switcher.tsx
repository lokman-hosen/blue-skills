import { router } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { Check, Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import locale from '@/routes/locale';

type LanguageSwitcherProps = {
    /** `compact` shows only the icon and code (top bars); `full` shows the native name. */
    variant?: 'compact' | 'full';
    className?: string;
};

export function LanguageSwitcher({ variant = 'compact', className }: LanguageSwitcherProps) {
    const { t, locale: current } = useTranslation();
    const { locales } = usePage().props;
    const active = locales.find((option) => option.code === current);

    const choose = (code: string) => {
        if (code !== current) {
            router.post(locale.update.url(), { locale: code }, { preserveScroll: true });
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size={variant === 'compact' ? 'sm' : 'default'}
                    className={cn('min-h-11 gap-1.5 sm:min-h-9', className)}
                    aria-label={`${t('language.label')}: ${active?.nativeName ?? current}`}
                >
                    <Languages aria-hidden />
                    <span lang={current}>
                        {variant === 'compact'
                            ? current.toUpperCase()
                            : (active?.nativeName ?? current)}
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-56">
                <DropdownMenuLabel>{t('language.choose')}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {locales.map((option) => (
                    <DropdownMenuItem
                        key={option.code}
                        onSelect={() => choose(option.code)}
                        className="min-h-11 gap-3"
                        aria-current={option.code === current ? 'true' : undefined}
                    >
                        <span
                            lang={option.code}
                            dir={option.rtl ? 'rtl' : 'ltr'}
                            className="font-semibold"
                        >
                            {option.nativeName}
                        </span>
                        <span className="text-xs text-muted">{option.englishName}</span>
                        {option.code === current && (
                            <Check aria-hidden className="ms-auto size-4 text-brand" />
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
