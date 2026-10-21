import { cn } from '@/lib/utils';

const SEGMENT: Record<string, string> = {
    done: 'bg-ok',
    skipped: 'bg-ok/40',
    current: 'bg-stamp',
    blocked: 'bg-bad',
    pending: 'bg-surface-2',
};

/** A case's stages as a compact bar (prototype a-cases "Progress"). */
export function CaseProgress({ progress, label }: { progress: string[]; label: string }) {
    return (
        <div role="img" aria-label={label} className="flex min-w-32 gap-0.5">
            {progress.map((status, index) => (
                <span
                    key={index}
                    className={cn('h-2 flex-1 rounded-sm', SEGMENT[status] ?? SEGMENT.pending)}
                />
            ))}
        </div>
    );
}
