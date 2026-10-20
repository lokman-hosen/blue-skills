import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ArrowRight, Check } from 'lucide-react';
import VoiceController from '@/actions/App/Http/Controllers/Worker/VoiceController';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import worker from '@/routes/worker';

const stepLinks: Record<string, NonNullable<InertiaLinkProps['href']> | undefined> = {
    trade: worker.basics.edit(),
    answers: VoiceController.show(),
    skillshow: worker.skillshow.edit(),
    evidence: worker.passport.edit('evidence'),
};

/**
 * Progressive onboarding (spec §3). Suggests the next step; never blocks the portal.
 */
export function ProfileCompletionCard({
    completion,
    className,
}: {
    completion: App.Domain.Worker.Data.ProfileCompletionData;
    className?: string;
}) {
    const { t, formatNumber } = useTranslation();
    const next = completion.next ? stepLinks[completion.next] : undefined;

    return (
        <section
            className={cn(
                'flex flex-col gap-3 rounded-2xl border border-line bg-surface p-4',
                className,
            )}
            aria-labelledby="profile-completion"
        >
            <div className="flex items-baseline justify-between gap-2">
                <h2 id="profile-completion" className="font-semibold">
                    {t('worker.completion.title')}
                </h2>
                <span className="font-mono text-sm font-semibold text-brand">
                    {formatNumber(completion.percent)}%
                </span>
            </div>
            <div
                className="h-2 overflow-hidden rounded-full bg-surface-2"
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={completion.percent}
                aria-labelledby="profile-completion"
            >
                <div
                    className="h-full rounded-full bg-brand"
                    style={{ width: `${completion.percent}%` }}
                />
            </div>
            <ul className="grid gap-1.5 text-sm sm:grid-cols-2">
                {completion.steps.map((step) => (
                    <li
                        key={step.key}
                        className={cn(
                            'flex items-center gap-2',
                            step.done ? 'text-ink' : 'text-muted',
                        )}
                    >
                        <span
                            className={cn(
                                'grid size-5 shrink-0 place-items-center rounded-full',
                                step.done ? 'bg-ok-soft text-ok' : 'border border-line',
                            )}
                        >
                            {step.done && <Check aria-hidden className="size-3" />}
                        </span>
                        {t(`worker.completion.step.${step.key}`)}
                        <span className="sr-only">
                            ·{' '}
                            {step.done ? t('worker.completion.done') : t('worker.completion.todo')}
                        </span>
                    </li>
                ))}
            </ul>
            {completion.next && next && (
                <Button asChild className="min-h-11 self-start">
                    <Link href={next}>
                        {t(`worker.completion.next.${completion.next}`)}
                        <ArrowRight aria-hidden className="size-4 rtl:rotate-180" />
                    </Link>
                </Button>
            )}
        </section>
    );
}
