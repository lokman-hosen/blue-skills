import { IdCard } from 'lucide-react';
import { cn } from '@/lib/utils';

export function Logo({ className, compact = false }: { className?: string; compact?: boolean }) {
    return (
        <span className={cn('inline-flex shrink-0 items-center gap-2', className)} dir="ltr">
            <span className="grid size-8 place-items-center rounded-lg bg-brand text-on-brand">
                <IdCard aria-hidden className="size-4" />
            </span>
            {!compact && (
                <span className="font-display text-[17px] font-extrabold tracking-tight">
                    BLUESKILLS<span className="text-stamp">.AE</span>
                </span>
            )}
        </span>
    );
}
