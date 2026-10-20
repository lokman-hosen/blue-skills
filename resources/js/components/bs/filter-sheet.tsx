import { SlidersHorizontal } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type FilterSheetProps = {
    children: ReactNode;
    title?: string;
    description?: string;
    activeCount?: number;
    onApply?: () => void;
    onClear?: () => void;
    className?: string;
};

/**
 * Filters render inline beside the results from `md` up, and in a bottom sheet on phones.
 */
export function FilterSheet({
    children,
    title,
    description,
    activeCount = 0,
    onApply,
    onClear,
    className,
}: FilterSheetProps) {
    const { t } = useTranslation();
    const isMobile = useIsMobile();
    const [open, setOpen] = useState(false);
    const heading = title ?? t('common.filters');

    if (!isMobile) {
        return (
            <aside
                aria-label={heading}
                className={cn(
                    'flex flex-col gap-4 rounded-xl border border-line bg-surface p-4',
                    className,
                )}
            >
                <div className="flex items-center justify-between gap-2">
                    <p className="font-semibold">{heading}</p>
                    {onClear && activeCount > 0 && (
                        <button
                            type="button"
                            onClick={onClear}
                            className="text-xs font-semibold text-brand hover:underline"
                        >
                            {t('common.clear_filters')}
                        </button>
                    )}
                </div>
                {children}
            </aside>
        );
    }

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <Button
                variant="outline"
                className={cn('min-h-11', className)}
                onClick={() => setOpen(true)}
            >
                <SlidersHorizontal aria-hidden />
                {activeCount > 0 ? t('common.filters_count', { count: activeCount }) : heading}
            </Button>
            <SheetContent side="bottom" className="max-h-[85svh] rounded-t-2xl bg-surface pb-safe">
                <SheetHeader className="pe-14">
                    <SheetTitle>{heading}</SheetTitle>
                    {description && <SheetDescription>{description}</SheetDescription>}
                </SheetHeader>
                <div className="flex-1 overflow-y-auto px-4">{children}</div>
                <SheetFooter className="flex-row gap-2 border-t border-line">
                    {onClear && (
                        <Button variant="outline" className="min-h-11 flex-1" onClick={onClear}>
                            {t('common.clear_filters')}
                        </Button>
                    )}
                    <Button
                        className="min-h-11 flex-1"
                        onClick={() => {
                            onApply?.();
                            setOpen(false);
                        }}
                    >
                        {t('common.show_results')}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
