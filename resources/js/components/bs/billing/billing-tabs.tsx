import { FilterPill } from '@/components/bs/filter-pill';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

/** Finance sections: organizations, invoices, plans. */
export function BillingTabs({ active }: { active: 'organizations' | 'invoices' | 'plans' }) {
    const { t } = useTranslation();

    return (
        <nav aria-label={t('nav.billing')} className="flex flex-wrap gap-1.5">
            <FilterPill
                href={admin.billing.index()}
                active={active === 'organizations'}
                label={t('billing.tab.organizations')}
            />
            <FilterPill
                href={admin.billing.invoices.index()}
                active={active === 'invoices'}
                label={t('billing.tab.invoices')}
            />
            <FilterPill
                href={admin.billing.plans.index()}
                active={active === 'plans'}
                label={t('billing.tab.plans')}
            />
        </nav>
    );
}
