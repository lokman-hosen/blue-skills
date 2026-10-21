# Translation to-do

Strings that fall back to English because a human translation is missing. Never machine-translate
silently: add the key here, and remove it once a reviewed translation lands in `lang/{locale}.json`.

| Key | Missing in | Added in | Notes |
|-----|------------|----------|-------|
| Taxonomy names (sectors, occupations, specializations, skills) | all except `en` | Module 0 | Seeded English only (D-017) |
| All keys in `lang/bn.json` except `nav.home`, `nav.jobs`, `nav.applications`, `nav.messages`, `nav.passport`, `language.choose` (taken from the prototype) | bn (draft, needs native review) | Module 0 | First-draft Bangla written during development; must be reviewed before launch |
| All interface keys in `lang/en.json` | hi, ur, ne, si, ta | Module 0 | No `lang/{locale}.json` yet; English fallback is shown |
| Starter-kit auth and settings screens (hard-coded English) | all | Module 0 | Moves to `lang/*.json` in Module 1 (D-029) |
| Module 2 keys: `worker.*`, `voice.*`, `skillshow.*`, `privacy.*`, `admin.skillshows.*`, `evidence.type.*`, `proficiency.*`, `salary_period.*`, `visibility.*`, `consent.purpose.*` | bn (draft, needs native review) | Module 2 | Covered by the "all bn keys are drafts" row; listed so reviewers check the Voice-to-CV questions and examples first |
| `VoiceVocabulary` Bangla words (countries, trades, skills, number words) | bn (draft, needs native review) | Module 2 | Used by the rules-based extractor (D-047); add hi, ur, ne words before those locales launch |
| Module 3 keys: `jobs.*`, `admin.organizations.*`, `admin.jobs.*`, `admin.review.*`, `review_reason.*`, `review_decision.*`, `report.category.*`, `dashboard.jobs.*`, organization and job review emails | bn (draft, needs native review) | Module 3 | Review the worker-facing ones first: `jobs.search.*`, `jobs.listing.*`, `jobs.match.*`, `jobs.report.*` |
| Job search over taxonomy names | all except `en` | Module 3 | Search matches English trade and skill names only until taxonomy translations exist (D-017) |
| Module 4 keys: `applications.*`, `documents.*`, `consent.item.*`, `document_type.*`, `interview_mode.*`, `interview_outcome.*`, `rejection_reason.*`, new `status.*` | bn (draft, needs native review) | Module 4 | Review the worker-facing apply, offer and withdraw texts first |
| Module 6 keys: `licence.*`, `mandates.*`, `admin.mandates.*`, `jobs.mandate.*`, `jobs.needs_mandate.*`, mandate notifications | bn (draft, needs native review) | Module 6 | Covered by the "all bn keys are drafts" row; check the agency-facing mandate errors first |
| Module 6 case documents: `mobility.document.*`, `status.doc_*`, document events and notifications | bn (draft, needs native review) | Module 6 | Check the worker-facing sharing and contract texts first |
| Module 6 agency workspace: `mobility.list.*`, `candidates.*`, `compliance.*`, `dashboard.licence.*`, `dashboard.cases.*`, `job_paused_mandate` notification | bn (draft, needs native review) | Module 6 | Agency-facing; review after the worker texts |
| Module 7 keys: `courses.*`, `cohorts.*`, `privacy.institutes.*`, `status.cohort_*`, `status.member_*`, institute notifications | bn (draft, needs native review) | Module 7 | Review the worker-facing invite page (`cohorts.invite.*`) and `privacy.institutes.*` first |
| Module 8 keys: `admin.evidence.*`, `admin.overview.*`, `trust.*`, `trust_reason.*`, `appeals.*`, trust and evidence notifications | bn (draft, needs native review) | Module 8 | Review the worker-facing `appeals.*`, suspension notices and `notification.job_suspended.*` first |
