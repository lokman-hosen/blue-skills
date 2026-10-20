import { RefreshCw, TriangleAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type ErrorStateProps = {
    title?: string;
    /** Say what went wrong and how to fix it. */
    description?: string;
    onRetry?: () => void;
    className?: string;
};

export function ErrorState({ title, description, onRetry, className }: ErrorStateProps) {
    const { t } = useTranslation();

    return (
        <div
            role="alert"
            className={cn(
                'flex flex-col items-center gap-3 rounded-2xl border border-bad/30 bg-bad-soft px-6 py-10 text-center',
                className,
            )}
        >
            <TriangleAlert aria-hidden className="size-7 text-bad" />
            <div className="flex max-w-sm flex-col gap-1">
                <p className="font-display text-lg font-bold text-ink">
                    {title ?? t('state.error.title')}
                </p>
                <p className="text-sm text-ink/80">{description ?? t('state.error.body')}</p>
            </div>
            {onRetry && (
                <Button variant="outline" onClick={onRetry} className="min-h-11">
                    <RefreshCw aria-hidden />
                    {t('common.retry')}
                </Button>
            )}
        </div>
    );
}
