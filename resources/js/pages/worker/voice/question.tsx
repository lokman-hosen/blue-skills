import { Head, Link, router, usePoll } from '@inertiajs/react';
import { AlertCircle, Check, Keyboard, Mic, Pencil, Square } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import VoiceController from '@/actions/App/Http/Controllers/Worker/VoiceController';
import { PageHeader } from '@/components/bs/page-header';
import { VoiceFieldsEditor } from '@/components/bs/voice-fields-editor';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useVoiceRecorder } from '@/hooks/use-voice-recorder';
import { useTranslation } from '@/lib/i18n';
import { describeVoiceFields, optionLookup, VOICE_QUESTIONS } from '@/lib/passport';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

type Props = {
    question: App.Domain.Worker.Enums.VoiceQuestion;
    progress: App.Domain.Worker.Data.VoiceProgressData[];
    answer: App.Domain.Worker.Data.VoiceAnswerData | null;
    startTyping: boolean;
    voiceAvailable: boolean;
    occupationSlug: string | null;
    options: App.Domain.Worker.Data.ProfileOptionsData;
    maxSeconds: number;
    maxTypedCharacters: number;
};

type Mode = 'speak' | 'type' | 'edit';

/** Voice-to-CV, one question at a time (spec §3, prototype "Voice-to-CV"). */
export default function VoiceQuestion({
    question,
    progress,
    answer,
    startTyping,
    voiceAvailable,
    occupationSlug,
    options,
    maxSeconds,
    maxTypedCharacters,
}: Props) {
    const { t, formatNumber } = useTranslation();
    const lookup = useMemo(() => optionLookup(options), [options]);
    const index = VOICE_QUESTIONS.indexOf(question);
    const processing = answer?.status === 'pending' || answer?.status === 'transcribed';
    const [mode, setMode] = useState<Mode>(startTyping || !voiceAvailable ? 'type' : 'speak');
    const [text, setText] = useState('');
    const [error, setError] = useState<string | undefined>();
    const [uploading, setUploading] = useState(false);

    // Reset the local state when the worker moves to another question.
    const [shownQuestion, setShownQuestion] = useState(question);

    if (shownQuestion !== question) {
        setShownQuestion(question);
        setMode(startTyping || !voiceAvailable ? 'type' : 'speak');
        setText('');
        setError(undefined);
    }

    const { start: startPolling, stop: stopPolling } = usePoll(
        1500,
        { only: ['answer', 'progress'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (processing) {
            startPolling();
        } else {
            stopPolling();
        }
    }, [processing, startPolling, stopPolling]);

    const send = (data: { audio: File } | { text: string }) => {
        router.post(VoiceController.submit.url(question), data, {
            forceFormData: 'audio' in data,
            preserveScroll: true,
            onStart: () => setUploading(true),
            onFinish: () => setUploading(false),
            onError: (errors) => setError(errors.audio ?? errors.text),
            onSuccess: () => {
                setError(undefined);
                setText('');
                setMode('speak');
            },
        });
    };

    const recorder = useVoiceRecorder({ maxSeconds, onRecorded: (file) => send({ audio: file }) });
    const understood = describeVoiceFields(
        question,
        answer?.fields ?? null,
        lookup,
        t,
        formatNumber,
    );
    const hasResult = answer?.status === 'extracted' || answer?.status === 'confirmed';

    return (
        <>
            <Head title={t('voice.title')} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-5">
                <PageHeader
                    eyebrow={t('voice.step', {
                        step: formatNumber(index + 1),
                        total: formatNumber(VOICE_QUESTIONS.length),
                    })}
                    title={t(`voice.q.${question}`)}
                    back={{ href: dashboard(), label: t('voice.save_later') }}
                />

                <ol className="flex gap-1" aria-label={t('voice.progress')}>
                    {progress.map((item) => (
                        <li key={item.question} className="flex-1">
                            <Link
                                href={VoiceController.show.url({ query: { q: item.question } })}
                                className={cn(
                                    'block h-1.5 rounded-full',
                                    item.confirmed
                                        ? 'bg-brand'
                                        : item.question === question
                                          ? 'bg-brand/40'
                                          : 'bg-surface-2',
                                )}
                                aria-label={`${t(`voice.label.${item.question}`)} · ${
                                    item.confirmed ? t('voice.confirmed') : t('voice.not_confirmed')
                                }`}
                                aria-current={item.question === question ? 'step' : undefined}
                            />
                        </li>
                    ))}
                </ol>

                <section
                    className="rounded-2xl border border-line bg-surface p-5"
                    aria-live="polite"
                >
                    {processing || uploading ? (
                        <div className="flex flex-col items-center gap-3 py-8 text-center">
                            <Spinner className="size-8 text-brand" />
                            <p className="font-semibold">{t('voice.processing')}</p>
                            <p className="text-sm text-muted">{t('voice.processing_hint')}</p>
                        </div>
                    ) : mode === 'edit' ? (
                        <VoiceFieldsEditor
                            key={`${question}-${answer?.id ?? 'new'}`}
                            question={question}
                            initial={answer?.fields ?? null}
                            answerId={answer && answer.status !== 'failed' ? answer.id : null}
                            options={options}
                            lookup={lookup}
                            occupationSlug={occupationSlug}
                            onCancel={() => setMode('speak')}
                        />
                    ) : hasResult && answer ? (
                        <div className="flex flex-col gap-3">
                            {answer.transcript && (
                                <div className="rounded-xl bg-surface-2 p-3">
                                    <p className="mb-1 eyebrow text-muted">
                                        {answer.typed
                                            ? t('voice.your_typed_answer')
                                            : t('voice.transcript')}
                                    </p>
                                    <p className="text-[15px]">“{answer.transcript}”</p>
                                </div>
                            )}
                            <p className="text-[13px] font-semibold text-muted">
                                {t('voice.we_understood')}
                            </p>
                            <div className="rounded-xl border-2 border-brand/50 bg-brand-soft/40 p-4">
                                <p className="text-xs text-muted">{t(`voice.label.${question}`)}</p>
                                {understood.value ? (
                                    <>
                                        <p className="font-display text-xl font-bold">
                                            {understood.value}
                                        </p>
                                        {understood.detail && (
                                            <p className="mt-1 text-[11.5px] text-muted">
                                                {understood.detail}
                                            </p>
                                        )}
                                    </>
                                ) : (
                                    <p className="text-sm font-semibold text-warn">
                                        {t('voice.not_understood')}
                                    </p>
                                )}
                            </div>
                            {answer.status === 'confirmed' ? (
                                <p className="flex items-center gap-1.5 text-sm font-semibold text-ok">
                                    <Check aria-hidden className="size-4" />
                                    {t('voice.confirmed')}
                                </p>
                            ) : (
                                understood.value && (
                                    <p className="mt-1 text-center font-semibold">
                                        {t('voice.is_correct')}
                                    </p>
                                )
                            )}
                            {understood.value && answer.status !== 'confirmed' && (
                                <Button
                                    className="min-h-12 w-full text-base"
                                    onClick={() =>
                                        router.post(
                                            VoiceController.confirm.url(question),
                                            {
                                                answer_id: answer.id,
                                                ...storedFields(question, answer.fields),
                                            } as never,
                                            {
                                                preserveScroll: true,
                                                onError: () => setMode('edit'),
                                            },
                                        )
                                    }
                                >
                                    <Check aria-hidden className="size-4" />
                                    {t('voice.yes_correct')}
                                </Button>
                            )}
                            <div className="grid grid-cols-2 gap-2">
                                <Button
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() => setMode('edit')}
                                >
                                    <Pencil aria-hidden className="size-4" />
                                    {understood.value ? t('voice.edit') : t('voice.fill_in')}
                                </Button>
                                {voiceAvailable ? (
                                    <Button
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={() => void recorder.start()}
                                    >
                                        <Mic aria-hidden className="size-4" />
                                        {t('voice.speak_again')}
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={() => setMode('type')}
                                    >
                                        <Keyboard aria-hidden className="size-4" />
                                        {t('voice.type_again')}
                                    </Button>
                                )}
                            </div>
                        </div>
                    ) : mode === 'type' ? (
                        <form
                            className="flex flex-col gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                send({ text });
                            }}
                        >
                            <Label htmlFor="voice-text">{t(`voice.label.${question}`)}</Label>
                            <textarea
                                id="voice-text"
                                value={text}
                                onChange={(event) => setText(event.target.value)}
                                maxLength={maxTypedCharacters}
                                rows={3}
                                placeholder={t(`voice.example.${question}`)}
                                className="min-h-24 w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-bad"
                                aria-invalid={!!error}
                                autoFocus
                            />
                            <InputError message={error} />
                            <Button
                                type="submit"
                                className="min-h-12 text-base"
                                disabled={text.trim() === ''}
                            >
                                {t('voice.send_answer')}
                            </Button>
                            <div className="flex flex-wrap justify-center gap-x-4 gap-y-1">
                                {voiceAvailable && (
                                    <button
                                        type="button"
                                        onClick={() => setMode('speak')}
                                        className="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-brand"
                                    >
                                        <Mic aria-hidden className="size-4" />
                                        {t('voice.use_voice')}
                                    </button>
                                )}
                                <button
                                    type="button"
                                    onClick={() => setMode('edit')}
                                    className="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-brand"
                                >
                                    <Pencil aria-hidden className="size-4" />
                                    {t('voice.fill_in')}
                                </button>
                            </div>
                            {!voiceAvailable && (
                                <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                                    {t('voice.unavailable')}
                                </p>
                            )}
                        </form>
                    ) : (
                        <div className="flex flex-col items-center gap-4 py-4 text-center">
                            {answer?.status === 'failed' && (
                                <p
                                    role="alert"
                                    className="flex items-start gap-2 rounded-lg bg-bad-soft px-3 py-2 text-start text-sm text-bad"
                                >
                                    <AlertCircle aria-hidden className="mt-0.5 size-4 shrink-0" />
                                    {t(`voice.error.${answer.errorCode ?? 'provider_error'}`)}
                                </p>
                            )}
                            {recorder.state === 'recording' ? (
                                <>
                                    <div className="voice-wave" aria-hidden>
                                        {Array.from({ length: 11 }, (_, i) => (
                                            <span key={i} />
                                        ))}
                                    </div>
                                    <p className="font-semibold text-brand">
                                        {t('voice.listening')}
                                    </p>
                                    <p className="font-mono text-sm text-muted" dir="ltr">
                                        {`${clock(recorder.elapsed)} / ${clock(maxSeconds)}`}
                                    </p>
                                    <Button
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={recorder.stop}
                                    >
                                        <Square aria-hidden className="size-4" />
                                        {t('voice.stop')}
                                    </Button>
                                </>
                            ) : (
                                <>
                                    <button
                                        type="button"
                                        onClick={() => void recorder.start()}
                                        disabled={
                                            recorder.state === 'requesting' ||
                                            recorder.state === 'unsupported'
                                        }
                                        className="grid size-28 mic-pulse place-items-center rounded-full bg-brand text-on-brand disabled:opacity-60"
                                        aria-label={t('voice.tap_to_speak')}
                                    >
                                        <Mic aria-hidden className="size-12" />
                                    </button>
                                    <p className="font-semibold">{t('voice.tap_to_speak')}</p>
                                    <p className="max-w-xs text-sm text-muted">
                                        {t(`voice.example.${question}`)}
                                    </p>
                                    {(recorder.state === 'denied' ||
                                        recorder.state === 'unsupported') && (
                                        <p role="alert" className="text-sm font-semibold text-bad">
                                            {t(`voice.mic_${recorder.state}`)}
                                        </p>
                                    )}
                                    <InputError message={error} />
                                    <button
                                        type="button"
                                        onClick={() => setMode('type')}
                                        className="inline-flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-brand"
                                    >
                                        <Keyboard aria-hidden className="size-4" />
                                        {t('voice.type_instead')}
                                    </button>
                                </>
                            )}
                        </div>
                    )}
                </section>

                <div className="flex items-center justify-between gap-2">
                    {index > 0 ? (
                        <Link
                            href={VoiceController.show.url({
                                query: { q: VOICE_QUESTIONS[index - 1] },
                            })}
                            className="inline-flex min-h-11 items-center text-sm font-semibold text-brand"
                        >
                            {t('voice.previous')}
                        </Link>
                    ) : (
                        <span />
                    )}
                    {progress.some((item) => item.confirmed) && (
                        <Link
                            href={VoiceController.review.url()}
                            className="inline-flex min-h-11 items-center text-sm font-semibold text-brand"
                        >
                            {t('voice.review_answers')}
                        </Link>
                    )}
                </div>

                <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                    {t('voice.privacy_note')}
                </p>
            </div>
        </>
    );
}

function clock(seconds: number): string {
    return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
}

/** The understood fields in the request shape, for one-tap confirmation. */
function storedFields(
    question: App.Domain.Worker.Enums.VoiceQuestion,
    fields: App.Domain.Worker.Data.VoiceFieldsData | null,
): Record<string, unknown> {
    if (!fields) {
        return {};
    }

    switch (question) {
        case 'occupation':
            return { occupation: fields.occupation, specialization: fields.specialization };
        case 'experience':
            return { years_experience: fields.yearsExperience };
        case 'countries':
            return { countries: fields.countries ?? [] };
        case 'skills':
            return { skills: fields.skills ?? [] };
        case 'passport':
            return {
                passport_status: fields.passportStatus,
                passport_valid_until_year: fields.passportValidUntilYear,
            };
        case 'availability':
            return { availability: fields.availability };
        case 'destinations':
            return { destinations: fields.destinations ?? [] };
    }
}
