import type { LucideIcon } from 'lucide-react';
import { Inbox } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type EmptyStateProps = {
    title: string;
    description?: string;
    icon?: LucideIcon;
    action?: ReactNode;
    className?: string;
};

export function EmptyState({
    title,
    description,
    icon: Icon = Inbox,
    action,
    className,
}: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-3 rounded-2xl border border-dashed border-line bg-surface px-6 py-10 text-center',
                className,
            )}
        >
            <span className="grid size-12 place-items-center rounded-xl bg-surface-2 text-brand">
                <Icon aria-hidden className="size-6" />
            </span>
            <div className="flex max-w-sm flex-col gap-1">
                <p className="font-display text-lg font-bold">{title}</p>
                {description && <p className="text-sm text-muted">{description}</p>}
            </div>
            {action}
        </div>
    );
}
