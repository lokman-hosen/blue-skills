import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Reading widths for a page's content. Every cap is wider than a phone, so on phones all
 * three behave the same; from `md` they keep forms and long text readable. Content always
 * starts at the inline-start edge (next to the side navigation), never floats centred.
 */
const WIDTHS = {
    /** Lists, grids and dashboards: everything the shell allows. */
    full: '',
    /** Forms and step-by-step flows. */
    form: 'max-w-[720px]',
    /** Long text such as consent and policy explanations. */
    prose: 'max-w-[70ch]',
} as const;

export type PageWidth = keyof typeof WIDTHS;

/**
 * The page wrapper inside a workspace shell (D-114). The shell's `<main>` owns the outer
 * padding and the overall cap ({@link WORKER_MAIN_CLASSES}); pages pick a width here instead
 * of hard-coding their own `max-w-*`.
 */
export function PageContainer({
    width = 'full',
    className,
    children,
}: {
    width?: PageWidth;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('flex w-full min-w-0 flex-col gap-5', WIDTHS[width], className)}>
            {children}
        </div>
    );
}

/**
 * The worker shell's `<main>`: phones keep the 16px safe-area gutter and the 768px column
 * exactly as before; from `md` content fills the width with 24px padding; from `lg` (side
 * navigation visible) it sits against the navigation with 32–40px padding, capped at 1600px.
 */
export const WORKER_MAIN_CLASSES = cn(
    'mx-auto w-full max-w-3xl min-w-0 flex-1 py-5 gutter pb-28 focus:outline-none',
    'md:max-w-none md:px-6',
    'lg:mx-0 lg:max-w-[1600px] lg:px-8 lg:pb-8 xl:px-10',
);
