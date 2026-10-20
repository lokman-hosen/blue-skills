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
