import { ChevronDown } from 'lucide-react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * A styled native <select>: best keyboard, screen reader and mobile picker support, and it
 * submits with Inertia's <Form> like any input.
 */
export function NativeSelect({ className, children, ...props }: ComponentProps<'select'>) {
    return (
        <div className="relative">
            <select
                className={cn(
                    'h-11 w-full appearance-none rounded-md border border-input bg-surface ps-3 pe-9 text-base text-ink shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-bad md:text-sm',
                    className,
                )}
                {...props}
            >
                {children}
            </select>
            <ChevronDown
                aria-hidden
                className="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-muted"
            />
        </div>
    );
}
