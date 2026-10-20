import { Form, Head, Link } from '@inertiajs/react';
import { Check, Pencil } from 'lucide-react';
import { useMemo } from 'react';
import VoiceController from '@/actions/App/Http/Controllers/Worker/VoiceController';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { describeVoiceFields, optionLookup, VOICE_QUESTIONS } from '@/lib/passport';
import { dashboard } from '@/routes';

type Props = {
    answers: App.Domain.Worker.Data.VoiceAnswerData[];
    progress: App.Domain.Worker.Data.VoiceProgressData[];
    options: App.Domain.Worker.Data.ProfileOptionsData;
};

/** Phase C: the worker checks every answer before it goes on the Skill Passport. */
export default function VoiceReview({ answers, progress, options }: Props) {
    const { t, formatNumber } = useTranslation();
    const lookup = useMemo(() => optionLookup(options), [options]);
    const allConfirmed = progress.every((item) => item.confirmed);

    return (
        <>
            <Head title={t('voice.review_title')} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-5">
                <PageHeader
                    title={t('voice.review_title')}
                    description={t('voice.review_body')}
                    back={{ href: VoiceController.show.url(), label: t('voice.back_to_questions') }}
                />

                <ul className="rounded-2xl border border-line bg-surface px-4">
                    {VOICE_QUESTIONS.map((question) => {
                        const answer = answers.find((item) => item.question === question) ?? null;
                        const described = describeVoiceFields(
                            question,
                            answer?.fields ?? null,
                            lookup,
                            t,
                            formatNumber,
                        );

                        return (
                            <li
                                key={question}
                                className="flex items-center gap-3 border-b border-line py-3 last:border-0"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="text-xs text-muted">
                                        {t(`voice.label.${question}`)}
                                    </p>
                                    <p className="text-[14.5px] font-semibold break-words">
                                        {described.value ?? t('voice.not_answered')}
                                    </p>
                                </div>
                                {answer ? (
                                    <span className="text-ok" title={t('voice.confirmed')}>
                                        <Check aria-hidden className="size-4" />
                                        <span className="sr-only">{t('voice.confirmed')}</span>
                                    </span>
                                ) : (
                                    <StatusChip label={t('voice.not_confirmed')} tone="warn" />
                                )}
                                <Link
                                    href={VoiceController.show.url({ query: { q: question } })}
                                    className="grid size-11 shrink-0 place-items-center rounded-lg text-brand hover:bg-surface-2"
                                    aria-label={`${t('voice.edit')}: ${t(`voice.label.${question}`)}`}
                                >
                                    <Pencil aria-hidden className="size-4" />
                                </Link>
                            </li>
                        );
                    })}
                </ul>

                <Form {...VoiceController.apply.form()} className="flex flex-col gap-2">
                    {({ processing, errors }) => (
                        <>
                            {!allConfirmed && (
                                <p className="text-[12.5px] text-warn">{t('voice.confirm_all')}</p>
                            )}
                            <InputError message={errors.answers} />
                            <Button
                                type="submit"
                                className="min-h-12 text-base"
                                disabled={!allConfirmed || processing}
                            >
                                {processing && <Spinner />}
                                {t('voice.create_passport')}
                            </Button>
                            <Button asChild variant="ghost" className="min-h-11">
                                <Link href={dashboard()}>{t('voice.save_later')}</Link>
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
