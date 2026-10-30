# User manuals

The in-app **User Manual** (sidebar › User Manual, `/manual`) shows these files. There is one
manual per role. Everyone sees the manual for their own role(s); a Superuser can open every manual.
See `docs/decisions.md` D-110.

## Where things are

```
resources/manuals/
  en/                     English (the fallback for every other language)
    worker.md             one file per role; the file name is the role's value
    owner.md, hr_admin.md, recruiter.md, hiring_manager.md, viewer.md
    agency_owner.md, case_officer.md, compliance_reviewer.md
    institute_owner.md, trainer.md, assessor.md, placement_officer.md
    superuser.md, verification_officer.md, trust_officer.md, crm_agent.md, finance_officer.md
    shared/               sections used by several manuals
  bn/                     (later) Bangla manuals with the same file names
```

## How a manual file is written

Plain Markdown, no HTML (HTML is removed when the page is shown).

- The first line is the title: `# Manual for recruiters`.
- Text before the first `##` heading is the introduction.
- Every `## Heading` starts a section. Each section is listed in the table of contents and can be
  found with the search box. Keep `## Getting started` first and `## Common questions` last, and
  put the sections in between in the same order as the role's sidebar.
- Use `###` for smaller headings inside a section, numbered lists (`1.`) for steps and `**bold**`
  for the names of buttons and menu items, written exactly as they appear on screen.
- A line `@include shared/name` (on its own line) inserts `shared/name.md` at that point. Use it
  for text that is the same for several roles, so it is written once.

Write short sentences with everyday words. Only describe what the role can really do; if a
button or page changes, update every manual that mentions it (search the folder for its name).

## Editing a manual

Edit the `.md` file and deploy. No code changes are needed. `php artisan test --filter=Manual`
checks that every role still has a manual that starts with "Getting started".

## Adding a manual for a new role

1. Add the role to `App\Domain\Manual\Enums\ManualRole` in reading order (least access first,
   Superuser last), with the same value as the role in `OrganizationRole` or `StaffRole`.
2. Create `resources/manuals/en/{value}.md`.
3. Add the role's label as `role.{value}` in `lang/en.json` and `lang/bn.json` if it isn't there.

## Translating a manual

Copy the English file to `resources/manuals/{locale}/` with the same name (and any `shared/`
files it includes) and translate it. Until a language has its own file, readers see the English
manual with a short notice. List untranslated manuals in `docs/translation-todo.md`; never
machine-translate silently.
