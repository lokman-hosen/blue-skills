import { Head, Link } from '@inertiajs/react';
import { IdCard } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { ProfileCompletionCard } from '@/components/bs/profile-completion-card';
import { SkillPassportCard } from '@/components/bs/skill-passport-card';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { toSkillPassportCard } from '@/lib/passport';
import worker from '@/routes/worker';

type Props = {
    passport: App.Domain.Worker.Data.WorkerPassportData | null;
};

/** Worker home: profile progress and the Skill Passport. Jobs arrive in Module 3. */
export default function WorkerHome({ passport }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('nav.home')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={
                        passport
                            ? t('worker.home.greeting', { name: passport.name.split(' ')[0] })
                            : t('nav.home')
                    }
                    description={t('worker.home.body')}
                />
                {passport ? (
                    <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,380px)] lg:items-start">
                        {passport.completion.next !== null ? (
                            <ProfileCompletionCard completion={passport.completion} />
                        ) : (
                            <EmptyState
                                icon={IdCard}
                                title={t('worker.home.complete_title')}
                                description={t('worker.home.complete_body')}
                            />
                        )}
                        <div className="flex flex-col gap-2">
                            <SkillPassportCard passport={toSkillPassportCard(passport)} />
                            <Button asChild variant="outline" className="min-h-11">
                                <Link href={worker.passport.show()}>
                                    {t('worker.home.open_passport')}
                                </Link>
                            </Button>
                        </div>
                    </div>
                ) : (
                    <EmptyState
                        icon={IdCard}
                        title={t('state.coming_soon.title')}
                        description={t('placeholder.worker_home')}
                    />
                )}
            </div>
        </>
    );
}
