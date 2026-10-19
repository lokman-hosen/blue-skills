# BLUESKILLS component map

How the design-lock pack turns into `resources/js/Components/*.tsx` (Laravel + Inertia + React + TypeScript).
Every class in the pack is a standard Tailwind utility using the token colour names in
`assets/tailwind.config.js`, so markup copies across as `className`. The source column says where to copy it from.

## Foundations

| Pack file | App file | Notes |
|---|---|---|
| `assets/tokens.css` | `resources/css/tokens.css` (imported by `app.css`) | RGB channel variables, light + dark, `[data-theme]` override, focus ring, dialog/sheet/drawer, skeleton, sidebar collapse (`--sb`). |
| `assets/tailwind.config.js` | `tailwind.config.ts` → `theme.extend` | Same colour names: `bg surface surface2 line linestrong ink muted brand brandsoft onbrand stamp stampsoft ok oksoft warn warnsoft bad badsoft`. |
| `assets/app.js` → icons | `lucide-react` | Names in the pack follow Lucide (`passport` = `BookUser`, `zap`, `flame`, `fan`, `box`, `brick` = `BrickWall`…). Arrow/back icons get `className="rtl:-scale-x-100"` (pack: `.flip-rtl`). |
| `assets/app.js` → theme | `Hooks/useTheme.ts` | `'system' \| 'light' \| 'dark'`, stored per device, sets `data-theme` on `<html>`. |
| `assets/i18n.js` + `i18n-screens.js` | `lang/{en,bn,hi,ur,ne}.json` + `Hooks/useT.ts` | `t(key, vars)`; `en` is the fallback. Values inserted via `vars` are wrapped in U+2068/U+2069 so dates and names keep their order in Urdu. Translations are drafts: get a native-speaker review before launch. |
| `assets/app.js` → language | `Contexts/LocaleProvider.tsx` | Sets `lang` + `dir` on `<html>`. `ur` → `rtl`. Worker-facing pages only; org/admin stay English for now. |
| `assets/sample-data.js` | Inertia props | Types below. |

```ts
// resources/js/types/models.ts
type Tone = 'ok' | 'warn' | 'bad' | 'brand' | 'stamp' | 'neutral';
type Payer = 'Employer' | 'Worker' | '';
interface WorkerCost { item: string; amount: number | ''; cur?: string; payer: 'Worker' }
interface Job {
  id: string; title: string; occ: string; city: string; country: string; icon?: TradeIcon;
  employer: string; postedBy: string; agencyLicence: string; mandate: string;
  salary: number | ''; cur: string; hours: number | ''; days: number | ''; overtime: string; contract: number | '';
  accommodation: string; food: string; transport: string; medical: string; airfare: string;
  permitPayer: Payer; visaPayer: Payer; costs: WorkerCost[]; headcount: number | ''; deadline: string;
  checks: [label: string, tone: Tone][];      // only real platform checks
  match?: [requirement: string, met: 0 | 1][]; // rules-based, per signed-in worker
}
interface Candidate { id: string; name: string; ini: string; occ: string; exp: number; from: string; worked: string;
  gcc: boolean; passport: boolean /* said by worker */; ss: boolean; idv: boolean /* checked */; avail: string; langs: string; skills: string[]; match: number }
type Stage = 'applied' | 'reviewed' | 'qualified' | 'shortlisted' | 'interview' | 'tradeTest' | 'selected' | 'offer' | 'accepted';
```

## Layouts (`resources/js/Layouts/`)

| Component | Source | Props |
|---|---|---|
| `PublicLayout.tsx` | `layouts/public.html`, `screens/01-home.html` | `{ children; active?: 'jobs' \| 'employers' \| 'how' }`. Header, mobile menu `Drawer`, language `BottomSheet`, footer. |
| `WorkerLayout.tsx` | `layouts/worker.html` | `{ title: string; active: 'home' \| 'jobs' \| 'applications' \| 'messages' \| 'passport' \| 'settings'; hideTabs?: boolean; maxWidth?: '3xl' \| '5xl' \| '7xl'; children }`. Side nav at `lg+`, bottom tabs under `lg` (`hideTabs` on detail pages that have their own sticky action). |
| `WorkspaceLayout.tsx` | `layouts/org.html` | `{ org: { name; kind: 'employer' \| 'agency' \| 'institute'; place; verified: boolean }; user; nav: NavGroup[]; active: string; children }`. Collapsible sidebar (`data-collapsed`, remembered), mobile `Drawer`. |
| `AdminLayout.tsx` | `layouts/admin.html` | Same as `WorkspaceLayout` plus a permanent staff strip and the `MFA · role` badge. |
| `AuthLayout.tsx` | `layouts/auth.html`, `screens/03-signin-voice.html` | `{ children; showLanguage?: boolean }`. Centred card, max-w-md. |

`NavGroup = { label: string; items: { label: string; icon: IconName; href: string; badge?: number }[] }`

## Components (`resources/js/Components/`)

### Actions
| Component | Props | Rules |
|---|---|---|
| `Button.tsx` | `variant: 'primary' \| 'secondary' \| 'soft' \| 'ghost' \| 'danger'; size?: 'sm' \| 'md' \| 'lg'; icon?: IconName; iconEnd?: IconName; loading?: boolean; full?: boolean; as?: 'button' \| typeof Link` | Heights: sm 44, md 48, lg 56. Worker screens use `lg` + `full` for the one main action. Danger = `bg-bad text-onbrand`. |
| `IconButton.tsx` | `icon; label: string; size?: 44 \| 48` | `label` → `aria-label` + `title`. Org/admin only: worker actions always show a word. |
| `ListenButton.tsx` | `text: string \| (() => string); lang` | Speech synthesis; toast when not available. |

### Form
| Component | Props |
|---|---|
| `Field.tsx` | `label; hint?; error?; optional?; children` (wires `aria-describedby`, `aria-invalid`) |
| `TextInput.tsx` / `Textarea.tsx` / `Select.tsx` | native props + `invalid?: boolean`; select has the end chevron |
| `SearchInput.tsx` | `value; onChange; placeholder; rounded?: 'full' \| 'xl'` |
| `PhoneInput.tsx` | `countryCode; onCountryCode; value; onChange; codes: string[]` (always `dir="ltr"`) |
| `OtpInput.tsx` | `length = 6; value; onChange; error?` (auto-advance, backspace, paste; `dir="ltr"`) |
| `NumberStepper.tsx` | `value; onChange; min; max; unit: string` |
| `Checkbox.tsx` / `Radio.tsx` | `label; description?; …input props`: whole row ≥ 48px, `accent-brand` |
| `Switch.tsx` | `checked; onChange; label; description?`: `role="switch"`, knob uses `rtl:` translate |
| `ChoiceTileGroup.tsx` | `name; options: { value; label; sub?; icon?: IconName; lang?: string }[]; value; onChange; columns?` (trade, language, yes/no) |
| `LanguagePicker.tsx` | `value: Locale; onChange` (wraps `ChoiceTileGroup`, native names) |
| `FileUploader.tsx` | `accept; maxMb; multiple?; camera?: boolean; files: UploadItem[]; onAdd(files); onRemove(id)`: *Take photo* + *Choose file*; privacy line |
| `FileItem.tsx` | `{ name; sizeMb; state: 'uploading' \| 'done' \| 'error'; progress?; error? }` |

### Display
| Component | Props | Rules |
|---|---|---|
| `Chip.tsx` | `tone: Tone; icon?; size?: 'sm' \| 'md'; children` | Always icon + text. |
| `StatusChip.tsx` | `kind: 'stage' \| 'verification' \| 'evidence' \| 'job' \| 'skillshow' \| 'case' \| 'report'; status: string` | One map (`design-system.html#chips`) → tone, icon, label. A green verification chip only for a real check, and its label names the check. |
| `EvidenceBadge.tsx` | `source: 'checked' \| 'institute' \| 'worker' \| 'employer'` | "Checked by BLUESKILLS", "Attested by institute", "Said by you/worker", "Declared by employer". |
| `FilterChip.tsx` | `pressed; onToggle; icon?; children` / `onRemove` variant | 44px tall. |
| `SampleBadge.tsx` | none | Dashed "Sample data" tag. Remove in production. |
| `NotDisclosed.tsx` | none | Italic `text-warn` + info icon. **Never** replace with a guess. |
| `Alert.tsx` | `tone: 'info' \| 'ok' \| 'warn' \| 'bad'; title; children` | `bad` gets `role="alert"`. |
| `ProgressBar.tsx` | `value; max; label; tone?` | `role="progressbar"`. |
| `MatchMeter.tsx` | `met: number; total: number; items?: [string, boolean][]` | Text is always "X of Y requirements met". Green ≥ 75 %, else warn. No scores, no ranking by video. |
| `Avatar.tsx` | `initials; tone?` | Initials only, never a passport photo. |
| `StatCard.tsx` | `label; value; sub?; tone?` | |
| `Skeleton.tsx` / `Spinner.tsx` | `className` | |
| `EmptyState.tsx` / `ErrorState.tsx` | `icon; title; body; action?: { label; onClick }` | One line, one fix button. |
| `OfflineBanner.tsx` | none | |

### Jobs and workers
| Component | Props | Rules |
|---|---|---|
| `TruthCard.tsx` | `job: Job; showMatch?: boolean; onReport?(); id?` | Source: `assets/render.js → truthCard`. Big facts first: salary, **You pay** (Nothing / total by currency), contract. Sections: Work, Food/housing/travel, Visa & costs (itemised worker costs), Who is hiring (employer, recruiter licence, mandate), You & this job. Empty field → `NotDisclosed`. Footer: Listen + Report. |
| `TruthRow.tsx` | `icon; label; value?: ReactNode` | `value` empty → `NotDisclosed`. |
| `JobCard.tsx` | `job: Job; href; selected?; showMatch?` | Source: `render.js → jobCard`. Whole card is one link (`after:absolute after:inset-0`). `showMatch=false` when signed out. |
| `CandidateCard.tsx` | `candidate: Candidate; onInvite; onSave; onPlayVideo?` | **No phone, no passport scan, no documents.** "Passport ready" is labelled *said by worker*. |
| `SkillPassportCover.tsx` | `worker; passportId; checks: string[]; onShare; onEmployerView` | `.passport-cover` pattern; MRZ line decorative (`aria-hidden`). |
| `SkillShowCard.tsx` | `video: { title; duration; skills; status }` | Always shows "Worker demonstration · not certification". |
| `ConsentList.tsx` | `items: { id; title; sub?; icon; required?: boolean; defaultOn?: boolean }[]; value: string[]; onChange; viewers: string` | Per-item consent; required item locked on; passport copy off by default; phone never shared (static row). |
| `StageTracker.tsx` | `stages: Stage[]; current: number; dates?: Record<number, string>` | Vertical under `xl`, horizontal at `xl+` (9 labels need ~110px each). `aria-current="step"`. |
| `StageTrackerCompact.tsx` | `current; total; status: Stage; next?: string` | Segmented bar + "Step 4 of 9". |
| `NextStepPanel.tsx` | `title; body; handler?: { name; org }; action?` | Always says who handles the case. |
| `VoiceQuestion.tsx` | `index; total; question; state: 'idle' \| 'listening' \| 'processing' \| 'confirm' \| 'edit' \| 'error' \| 'blocked' \| 'saved'; transcript?; parsed?: { label; value }; onConfirm; onEdit; onRetry; onType` | **Never saves without explicit Yes.** Edit/type is always one tap away. |
| `ReportJobDialog.tsx` | `jobId; open; onClose; onSubmit(reason, note)` | Reasons: asked to pay, untrue pay/conditions, fake job, other. |

### Organisation and admin
| Component | Props |
|---|---|
| `DataTable.tsx` | `columns: { key; header; cell(row); align? }[]; rows; selectable?; selected; onSelect; mobileCard(row): ReactNode`. Table at `md+`, cards below, same actions. Chips in cells `whitespace-nowrap`. |
| `Pagination.tsx` | `page; pages; onPage` |
| `FilterPanel.tsx` | `groups: FilterGroup[]; value; onChange; onClear`. Renders inline at `lg+` and inside `BottomSheet` below. One state for both. |
| `WizardStepper.tsx` | `steps: string[]; current; onStep?`. Full stepper at `xl+`, "Step n of N" + bar below. |
| `PostJobWizard` (page section) | `job: Partial<Job>; countryRules: { noWorkerFees: boolean }; onSave; onSubmit`. Live `TruthCard` preview at `xl+`, preview sheet below. Lists fields that will show "Not disclosed". Blocks submit on country-rule breach or missing attestation. |
| `KanbanBoard.tsx` | `stages; totals: number[]; cards: PipelineCard[]; selected; onSelect; onMove(id, stage)`. Snap-scroll columns; "Move" opens a sheet (no drag-only interaction). |
| `BulkActionBar.tsx` | `count; stages; onMove(stage); onMessage; onSchedule; onReject; onClear` (fixed bottom, aligned to `--sb`) |
| `RejectDialog.tsx` | `count; reasons: string[]; onConfirm(reason, note)` (reason code required; worker sees a kind message, never the note) |
| `QueueList.tsx` | `items: { id; org; type; waiting; late: boolean; status }[]; currentId` |
| `DocumentViewer.tsx` | `files; current; page; pages; watermark: string`. Watermarked, download off, views logged. |
| `ComparisonTable.tsx` | `rows: { field; declared; document?; result: 'match' \| 'valid' \| 'declaredOnly' \| 'mismatch' }[]` |
| `DecisionPanel.tsx` | `checks: string[]; reasons: string[]; onDecide({ decision, reason?, message?, internalNote? })`. Approve needs every check; info/reject need a reason code. |
| `AuditLog.tsx` | `entries: { at; actor; action; icon }[]` |

### Overlays and feedback
| Component | Props | Notes |
|---|---|---|
| `Modal.tsx` | `open; onClose; title; children; footer?` | Native `<dialog>` + `showModal()`; backdrop click closes. Destructive confirms put the safe button first on phones (`flex-col-reverse`). |
| `BottomSheet.tsx` | `open; onClose; title; children; footer?` | `dialog.sheet`: bottom on phones, centred at `sm+`; safe-area padding. |
| `Drawer.tsx` | `open; onClose; side?: 'start'; children` | `dialog.drawer`, slides from the start edge (mirrors in RTL). |
| `Toast.tsx` + `Hooks/useToast.ts` | `toast(message, tone?)` | `role="status"`, 4s, sits above the bottom tabs. |
| `SaveBar.tsx` | `dirty; onSave; onUndo` | Above bottom tabs on phones (`bottom-16`), full width at `lg` beside the side nav. |

## Pages → components

| Screen | Page component | Uses |
|---|---|---|
| 01 Public home | `Pages/Home.tsx` | PublicLayout, SearchInput, ChoiceTile links, JobCard (`showMatch=false`), ListenButton |
| 02 Jobs search | `Pages/Worker/Jobs/Index.tsx` (+ `Show.tsx` on phones) | WorkerLayout, SearchInput, FilterChip, JobCard, TruthCard, EmptyState, ReportJobDialog |
| 03 Sign-in + Voice-to-CV | `Pages/Auth/WorkerSignIn.tsx`, `Pages/Worker/Onboarding/Voice.tsx` | AuthLayout, LanguagePicker, PhoneInput, OtpInput, VoiceQuestion, NumberStepper |
| 04 Skill Passport | `Pages/Worker/Passport.tsx` | SkillPassportCover, EvidenceBadge, SkillShowCard, ProgressBar, BottomSheet |
| 05 Apply + tracker | `Pages/Worker/Apply.tsx`, `Pages/Worker/Applications/Show.tsx` | JobCard, ConsentList, Alert (duplicate), StageTracker, NextStepPanel, Modal |
| 06 Post a job | `Pages/Org/Jobs/Create.tsx` | WorkspaceLayout, WizardStepper, Field…, TruthCard (preview), Alert (country rule), BottomSheet |
| 07 Talent search | `Pages/Org/Talent/Index.tsx` | FilterPanel, CandidateCard, MatchMeter, Modal (video) |
| 08 Pipeline | `Pages/Org/Pipeline/Show.tsx` | KanbanBoard, BulkActionBar, RejectDialog, BottomSheet (move) |
| 09 Verification queue | `Pages/Admin/Verification/Index.tsx` | AdminLayout, QueueList, Tabs, DocumentViewer, ComparisonTable, DecisionPanel, AuditLog |
| 10 Settings & privacy | `Pages/Worker/Settings.tsx` | LanguagePicker, Switch, Radio, SaveBar, Modal |
| 11 Profile setup | `Pages/Worker/Onboarding/Basics.tsx`, `Pages/Worker/Onboarding/Review.tsx` | WorkerLayout (`hideTabs`), ChoiceTileGroup (country, trade), TextInput, ProgressBar, BottomSheet (edit field). Every generated line needs an explicit Yes before the Passport is created. |
| 12 SkillShow | `Pages/Worker/SkillShow/Create.tsx` | FileUploader (`camera`, video), length check from video metadata (max 30 s), FilterChip skill tags (max 3), Alert. Status after send: "Being checked". |
| 13 Mobility case (worker) | `Pages/Worker/Cases/Show.tsx` | StageTracker (country template), NextStepPanel, ConsentList (per-document request), StatCard "You pay", ReportJobDialog (case reasons) |
| 14 Messages | `Pages/Worker/Messages/Index.tsx` (+ `Show.tsx` on phones) | ConversationList, MessageBubble, InterviewSlotCard, Composer (voice + text). Phone never shown. |
| 15 Public jobs | `Pages/Jobs/Index.tsx` | PublicLayout, SearchInput, FilterChip, JobCard + TruthCard (`showMatch=false`), sign-in banner, ReportJobDialog |
| 16 Org registration | `Pages/Auth/OrgRegister.tsx` | AuthLayout (`max-w-2xl`), WizardStepper, Field…, MFA setup (QR + key + OtpInput), FileUploader per evidence item, status timeline |
| 17 Licence & mandates | `Pages/Org/Agency/Mandates.tsx` | WorkspaceLayout (agency), licence card (`.passport-cover`), DataTable, StatusChip (`mandate`), BottomSheet (add mandate) |
| 18 Agency cases | `Pages/Org/Agency/Cases/Index.tsx` | StatCard, DataTable, case panel (StageTracker compact list, document chips, request Modal). Panel at `xl+`, BottomSheet below. |
| 19 Cohorts | `Pages/Org/Institute/Cohorts.tsx` | WorkspaceLayout (institute), StatCard, ProgressBar, Invite sheet with Tabs (link · QR · WhatsApp) |
| 20 Graduates | `Pages/Org/Institute/Graduates.tsx` | Tabs, DataTable (`selectable`), BulkActionBar, Modal (attest needs a confirm tick), Modal (can't-confirm reason) |
| 21 Trust reports | `Pages/Admin/Reports/Index.tsx` | AdminLayout, Tabs (reports · appeals), report list + detail, interim actions, DecisionPanel (reason codes TS-01…06), typed-name confirm for suspension. Appeals block the original decider. |
| 22 Countries & taxonomy | `Pages/Admin/Config/Countries.tsx`, `Pages/Admin/Config/Taxonomy.tsx` | Switch, Radio, Checkbox grid, reorderable step list (up/down buttons, no drag-only), SaveBar ("send for approval"), TreeView (`role="tree"`) + node editor |
| 23 Employer messages | `Pages/Org/Messages/Index.tsx` (+ `Show.tsx` on phones) | WorkspaceLayout, ConversationList (job + unread/agency filters), MessageBubble (worker · agency · own, translated with original), InterviewSlotCard, Modal (propose time, shows worker time zone), template Select, PayWarning (draft mentions money), candidate panel at `2xl+` / BottomSheet below. No phone numbers; participants listed above the thread. |

## Rules every component keeps
- 44px minimum target (worker main actions 56px), body text ≥ 16px on worker screens, nothing under 13px.
- Status = colour + icon + word. Logical utilities only (`ms/me/ps/pe/start/end`, `text-start`). Mirror directional icons.
- Phone numbers, OTP, IDs and money amounts are `dir="ltr"`/`tabular-nums`. Inserted values are bidi-isolated.
- Missing Truth Card data → "Not disclosed". Verified chips only for real checks. Voice answers confirmed before saving.
  SkillShow labelled "not certification". No passport scans or phone numbers in search. Matching shows "X of Y requirements met".
