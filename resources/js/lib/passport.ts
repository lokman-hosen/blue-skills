import type { SkillPassport } from '@/components/bs/skill-passport-card';
import type { Translate } from '@/lib/i18n';

type Options = App.Domain.Worker.Data.ProfileOptionsData;
type Fields = App.Domain.Worker.Data.VoiceFieldsData;
type Question = App.Domain.Worker.Enums.VoiceQuestion;

export const VOICE_QUESTIONS: Question[] = [
    'occupation',
    'experience',
    'countries',
    'skills',
    'passport',
    'availability',
    'destinations',
];

/** Name lookups for slugs and codes stored on voice answers. */
export function optionLookup(options: Options) {
    const occupations = new Map(options.occupations.map((item) => [item.slug, item]));
    const skills = new Map(options.skills.map((item) => [item.slug, item.name]));
    const countries = new Map(options.countries.map((item) => [item.code, item.name]));

    return {
        occupation: (slug: string | null) => (slug ? occupations.get(slug) : undefined),
        specialization: (occupationSlug: string | null, slug: string | null) =>
            occupationSlug && slug
                ? occupations
                      .get(occupationSlug)
                      ?.specializations.find((item) => item.slug === slug)
                : undefined,
        skill: (slug: string) => skills.get(slug) ?? slug,
        country: (code: string) => countries.get(code) ?? code,
        /** Skills typical for an occupation first, then every other skill. */
        skillsFor: (occupationSlug: string | null) => {
            const typical = occupationSlug ? (occupations.get(occupationSlug)?.skills ?? []) : [];
            const typicalSlugs = new Set(typical.map((item) => item.slug));

            return [...typical, ...options.skills.filter((item) => !typicalSlugs.has(item.slug))];
        },
    };
}

export type OptionLookup = ReturnType<typeof optionLookup>;

/**
 * What we understood, in words. `null` when the answer holds nothing for the question, so
 * the screen asks the worker to fill it in instead of showing a guess.
 */
export function describeVoiceFields(
    question: Question,
    fields: Fields | null,
    lookup: OptionLookup,
    t: Translate,
    formatNumber: (value: number) => string,
): { value: string | null; detail?: string } {
    if (!fields) {
        return { value: null };
    }

    switch (question) {
        case 'occupation': {
            const occupation = lookup.occupation(fields.occupation);
            const specialization = lookup.specialization(fields.occupation, fields.specialization);

            return occupation
                ? {
                      value: occupation.name,
                      detail: [occupation.sector, occupation.name, specialization?.name]
                          .filter(Boolean)
                          .join(' › '),
                  }
                : { value: null };
        }
        case 'experience':
            return {
                value:
                    fields.yearsExperience !== null
                        ? t('passport.years', { count: formatNumber(fields.yearsExperience) })
                        : null,
            };
        case 'countries':
            return {
                value: fields.countries?.length
                    ? fields.countries
                          .map((country) =>
                              country.years !== null
                                  ? t('voice.country_years', {
                                        country: lookup.country(country.code),
                                        years: formatNumber(country.years),
                                    })
                                  : lookup.country(country.code),
                          )
                          .join(', ')
                    : null,
            };
        case 'skills':
            return {
                value: fields.skills?.length
                    ? fields.skills.map((slug) => lookup.skill(slug)).join(', ')
                    : null,
            };
        case 'passport':
            return fields.passportStatus
                ? {
                      value: [
                          t(`passport.status.${fields.passportStatus}`),
                          fields.passportValidUntilYear
                              ? t('voice.valid_until', { year: fields.passportValidUntilYear })
                              : null,
                      ]
                          .filter(Boolean)
                          .join(' · '),
                  }
                : { value: null };
        case 'availability':
            return { value: fields.availability ? t(`availability.${fields.availability}`) : null };
        case 'destinations':
            return {
                value: fields.destinations?.length
                    ? fields.destinations.map((code) => lookup.country(code)).join(', ')
                    : null,
            };
    }
}

/** Countries with total years, for the Passport cover. */
export function countriesWorked(experiences: App.Domain.Worker.Data.WorkerExperienceData[]) {
    const totals = new Map<string, { code: string; name: string; years: number }>();

    for (const experience of experiences) {
        const current = totals.get(experience.countryCode) ?? {
            code: experience.countryCode,
            name: experience.countryName,
            years: 0,
        };
        current.years += experience.years ?? 0;
        totals.set(experience.countryCode, current);
    }

    return [...totals.values()];
}

export function toSkillPassportCard(
    passport: App.Domain.Worker.Data.WorkerPassportData,
): SkillPassport {
    return {
        code: passport.reference,
        name: passport.name,
        headline: passport.headline ?? passport.occupation?.name ?? null,
        nationality: passport.nationality,
        yearsExperience: passport.yearsExperience,
        passportStatus: passport.passportStatus,
        availability: passport.availability,
        countriesWorked: countriesWorked(passport.experiences),
        skills: passport.skills.map((skill) => skill.name),
        photoUrl: passport.photoUrl,
    };
}
