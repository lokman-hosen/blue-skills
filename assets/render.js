/* Markup renderers for repeated components, used by the screens. Each one mirrors a future React
   component (see docs/components.md) and produces the same classes as design-system.html.
   Text is wrapped in data-i18n spans, so BS.translate() / a language switch localises it. */
(function () {
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmt = n => Number(n).toLocaleString('en-US');
  const icon = (n, cls = 'w-5 h-5') => `<svg class="${cls} shrink-0" aria-hidden="true"><use href="#i-${n}"/></svg>`;
  // Translatable text: <span data-i18n="key">English</span>
  const L = (key, en, vars, tag = 'span', cls = '') => {
    let txt = en; if (vars) for (const k in vars) txt = txt.split(`{${k}}`).join(vars[k]);
    const v = vars ? ` data-i18n-vars='${esc(JSON.stringify(vars))}'` : '';
    return `<${tag}${cls ? ` class="${cls}"` : ''} data-i18n="${key}"${v}>${esc(txt)}</${tag}>`;
  };
  const TONE = { ok: 'bg-oksoft text-ok', warn: 'bg-warnsoft text-warn', bad: 'bg-badsoft text-bad', brand: 'bg-brandsoft text-brand', stamp: 'bg-stampsoft text-stamp', neutral: 'bg-surface2 text-muted' };
  const chip = (inner, tone = 'neutral', ic = '', extra = '') =>
    `<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-semibold ${TONE[tone]} ${extra}">${ic ? icon(ic, 'w-4 h-4') : ''}${inner}</span>`;
  const nd = () => `<span class="inline-flex items-center gap-1 italic font-medium text-warn">${icon('info', 'w-4 h-4')}${L('notDisclosed', 'Not disclosed')}</span>`;
  const val = v => (v === undefined || v === null || v === '') ? nd() : esc(v);

  // Worker-paid costs, totalled per currency ("EUR 140", "BDT 3,000")
  function workerCosts(j) {
    const items = (j.costs || []).filter(c => c.payer === 'Worker' && c.item);
    const by = {}; items.forEach(c => { const cur = c.cur || j.cur; by[cur] = (by[cur] || 0) + Number(c.amount || 0); });
    return { items, total: Object.entries(by).map(([cur, a]) => `${cur} ${fmt(a)}`).join(' + ') };
  }
  const metCount = j => ({ n: (j.match || []).filter(m => m[1]).length, t: (j.match || []).length });
  const CHECK_ICON = { ok: 'shield-check', warn: 'refresh', bad: 'x-circle', neutral: 'building' };
  // Platform checks are a fixed list, so their labels are translated; anything else shows as written.
  const CHECK_KEY = { 'Employer verified': 'chkEmployer', 'Agency licence verified': 'chkLicence', 'Mandate verified': 'chkMandate', 'Salary employer-declared': 'chkSalaryDecl',
    'Job under re-review after change': 'chkReReview', 'Mandate pending review': 'chkMandatePending', 'Route documents verified': 'chkRoute' };
  const checkLabel = t => CHECK_KEY[t] ? L(CHECK_KEY[t], t) : esc(t);

  /* <TruthCard job={job} /> */
  function truthCard(j, opt = {}) {
    const id = opt.id || ('tc-' + (j.id || 'draft').toLowerCase());
    const wc = workerCosts(j); const m = metCount(j);
    const row = (ic, key, en, v, tone = 'text-muted') =>
      `<div class="grid grid-cols-[1.5rem_minmax(0,7.5rem)_minmax(0,1fr)] sm:grid-cols-[1.5rem_minmax(0,9rem)_minmax(0,1fr)] gap-x-2 py-2">${icon(ic, 'w-5 h-5 mt-0.5 ' + tone)}${key ? L(key, en, null, 'dt', 'text-muted') : `<dt class="text-muted">${esc(en)}</dt>`}<dd class="font-medium min-w-0 break-words">${v}</dd></div>`;
    const sec = (key, en, rows) => `<section class="px-4 sm:px-5 py-3">${L(key, en, null, 'h4', 'text-[13px] font-bold uppercase tracking-wider text-muted')}<dl class="mt-1 divide-y divide-line/70">${rows}</dl></section>`;
    const payer = p => p === 'Employer' ? L('employerPays', 'Employer pays') : p === 'Worker' ? `<span class="font-semibold text-warn">${L('youPayCosts', 'You pay · see costs')}</span>` : nd();
    const salary = (j.salary === '' || j.salary == null) ? nd() : `${esc(j.cur)} ${fmt(j.salary)}`;
    const agency = !!j.agencyLicence;
    const checks = (j.checks || []).map(([t, tone]) => `<li>${chip(checkLabel(t), tone === 'neutral' ? 'neutral' : tone, CHECK_ICON[tone] || 'info', tone === 'neutral' ? '!bg-surface' : '')}</li>`).join('');
    const hours = j.hours && j.days ? L('hoursVal', '{h} hours a day · {d} days a week', { h: j.hours, d: j.days }) : nd();
    const pct = m.t ? Math.round(m.n / m.t * 100) : 0;
    return `<article class="rounded-2xl border border-line bg-surface overflow-hidden" aria-labelledby="${id}-t">
  <header class="bg-brandsoft px-4 sm:px-5 pt-4 pb-4">
    <div class="flex items-center gap-2">${L('tcTitle', 'Job Truth Card', null, 'span', 'text-[13px] font-bold uppercase tracking-wider text-brand')}<span class="ms-auto font-mono text-[13px] text-muted">${esc(j.id || 'DRAFT')}</span></div>
    <h3 id="${id}-t" class="mt-1 text-2xl font-bold leading-tight break-words">${j.title ? esc(j.title) : nd()}</h3>
    <p class="mt-0.5 flex items-center gap-1.5 text-muted">${icon('pin')}${j.city || j.country ? esc([j.city, j.country].filter(Boolean).join(', ')) : nd()}</p>
    ${checks ? `<ul class="mt-3 flex flex-wrap gap-2" aria-label="Checks">${checks}</ul>` : ''}
  </header>
  <div class="grid grid-cols-1 sm:grid-cols-3 border-b border-line">
    <div class="p-4 border-b sm:border-b-0 sm:border-e border-line"><p class="flex items-center gap-1.5 text-sm text-muted">${icon('money')}${L('salary', 'Salary')}</p><p class="text-2xl font-bold tabular-nums">${salary}</p>${L('perMonth', 'per month', null, 'p', 'text-sm text-muted')}</div>
    ${wc.items.length
      ? `<div class="p-4 border-b sm:border-b-0 sm:border-e border-line bg-warnsoft/60"><p class="flex items-center gap-1.5 text-sm text-warn font-semibold">${icon('alert')}${L('youPay', 'You pay')}</p><p class="text-2xl font-bold text-warn tabular-nums">${esc(wc.total)}</p>${L('costsCount', '{n} costs · see below', { n: wc.items.length }, 'p', 'text-sm text-muted')}</div>`
      : `<div class="p-4 border-b sm:border-b-0 sm:border-e border-line bg-oksoft/60"><p class="flex items-center gap-1.5 text-sm text-ok font-semibold">${icon('check-circle')}${L('youPay', 'You pay')}</p>${L('nothing', 'Nothing', null, 'p', 'text-2xl font-bold text-ok')}${L('noWorkerCosts', 'No worker costs', null, 'p', 'text-sm text-muted')}</div>`}
    <div class="p-4"><p class="flex items-center gap-1.5 text-sm text-muted">${icon('calendar')}${L('contract', 'Contract')}</p><p class="text-2xl font-bold tabular-nums">${j.contract ? L('months', '{n} months', { n: j.contract }) : nd()}</p>${j.deadline ? L('applyBy', 'Apply by {d}', { d: j.deadline }, 'p', 'text-sm text-muted') : ''}</div>
  </div>
  <div class="divide-y divide-line">
    ${sec('secWork', 'Work', row('clock', 'hours', 'Hours', hours) + row('plus', 'overtime', 'Overtime', val(j.overtime)) + row('users', 'workersNeeded', 'Workers needed', j.headcount ? `<span class="tabular-nums">${fmt(j.headcount)}</span>` : nd()))}
    ${sec('secLiving', 'Food, housing, travel', row('bed', 'housing', 'Housing', val(j.accommodation)) + row('food', 'food', 'Food', val(j.food)) + row('bus', 'transport', 'Transport', val(j.transport)) + row('medical', 'medical', 'Medical', val(j.medical)) + row('plane', 'airTicket', 'Air ticket', val(j.airfare)))}
    ${sec('secVisa', 'Visa and costs', row('passport', 'workPermit', 'Work permit', payer(j.permitPayer)) + row('file', 'visa', 'Visa', payer(j.visaPayer)) +
      (wc.items.length ? wc.items.map(c => row('money', null, c.item, `<span class="font-semibold text-warn tabular-nums">${esc(c.cur || j.cur)} ${c.amount === '' || c.amount == null ? '' : fmt(c.amount)}</span> ${L('youPayShort', '· you pay', null, 'span', 'font-semibold text-warn')}`, 'text-warn')).join('')
        : row('money', 'costsYouPay', 'Costs you pay', `<span class="font-semibold text-ok">${L('nothing', 'Nothing')}</span>`, 'text-ok')))}
    ${sec('secWho', 'Who is hiring', row('building', 'employer', 'Employer', `${val(j.employer)}${agency ? '' : `<span class="block text-sm font-normal text-muted">${esc(j.mandate || '')}</span>`}`) +
      (agency ? row('briefcase', 'recruiter', 'Recruiter', `${esc(j.postedBy)}<span class="block text-sm font-normal text-muted">${esc(j.agencyLicence)}</span>`) + row('file', 'mandate', 'Mandate', val(j.mandate)) : ''))}
    ${opt.match === false || !m.t ? '' : `<section class="px-4 sm:px-5 py-3">${L('secMatch', 'You and this job', null, 'h4', 'text-[13px] font-bold uppercase tracking-wider text-muted')}
      <div class="mt-2 flex items-center justify-between gap-3">${L('reqMet', '{n} of {t} requirements met', m, 'p', 'font-semibold')}${L('rulesBased', 'Rules-based', null, 'span', 'text-sm text-muted')}</div>
      <div class="mt-1.5 h-3 rounded-full bg-surface2 overflow-hidden" role="progressbar" aria-label="Requirements met" aria-valuemin="0" aria-valuemax="${m.t}" aria-valuenow="${m.n}"><div class="h-full rounded-full ${pct >= 75 ? 'bg-ok' : 'bg-warn'}" style="width:${pct}%"></div></div>
      <details class="mt-2 group"><summary class="inline-flex items-center gap-1 min-h-11 font-semibold text-brand cursor-pointer">${L('seeAllN', 'See all {n}', { n: m.t })}${icon('down', 'w-4 h-4 group-open:rotate-180 transition-transform')}</summary>
      <ul class="grid gap-1 sm:grid-cols-2 pb-2">${j.match.map(([t, ok]) => `<li class="flex items-center gap-2 ${ok ? '' : 'text-muted'}">${icon(ok ? 'check-circle' : 'x-circle', 'w-5 h-5 ' + (ok ? 'text-ok' : ''))}${esc(t)}</li>`).join('')}</ul></details></section>`}
  </div>
  <footer class="flex flex-wrap items-center gap-2 px-4 sm:px-5 py-3 bg-surface2/60 border-t border-line">
    <span id="${id}-say" class="sr-only">${esc(j.title)}. ${esc(j.city)}. ${j.salary ? `${esc(j.cur)} ${fmt(j.salary)}` : ''}. ${wc.items.length ? 'You pay ' + esc(wc.total) : 'You pay nothing'}.</span>
    <button type="button" data-speak="#${id}-say" class="inline-flex items-center gap-2 min-h-11 px-4 rounded-full bg-brandsoft text-brand font-semibold hover:bg-brandsoft/70">${icon('speaker')}${L('listen', 'Listen')}</button>
    <button type="button" data-open="report-dlg" class="ms-auto inline-flex items-center gap-2 min-h-11 px-3 rounded-xl text-bad font-semibold hover:bg-badsoft">${icon('flag')}${L('reportJob', 'Report this job')}</button>
  </footer>
</article>`;
  }

  /* <JobCard job={job} href selected match /> — whole card is one link; match=false when signed out */
  function jobCard(j, opt = {}) {
    const wc = workerCosts(j); const m = metCount(j);
    const verified = (j.checks || []).some(([t, tone]) => tone === 'ok' && /employer verified/i.test(t));
    const review = (j.checks || []).find(([, tone]) => tone === 'warn');
    const housing = j.accommodation ? esc(j.accommodation.split(' · ')[0]) : `<span class="italic font-medium text-warn">${L('housingND', 'Housing not disclosed')}</span>`;
    return `<article class="relative rounded-2xl border ${opt.selected ? 'border-brand ring-2 ring-brand/30' : 'border-line hover:border-brand/60'} bg-surface p-4 focus-within:ring-[3px] focus-within:ring-brand/50">
  <div class="flex items-start gap-3">
    <span class="grid place-items-center w-12 h-12 shrink-0 rounded-xl bg-stampsoft text-stamp">${icon(j.icon || 'briefcase', 'w-7 h-7')}</span>
    <div class="min-w-0 flex-1"><h3 class="text-lg font-bold leading-snug"><a href="${opt.href || '#'}" ${opt.selected ? 'aria-current="true"' : ''} class="after:absolute after:inset-0 focus:outline-none">${esc(j.title)}</a></h3><p class="text-muted truncate">${esc(j.employer)}</p></div>
    ${review ? chip(L('reReview', 'Re-review'), 'warn', 'refresh', '!px-2 !py-0.5 !text-[13px]') : verified ? chip(L('verified', 'Verified'), 'ok', 'shield-check', '!px-2 !py-0.5 !text-[13px]') : ''}
  </div>
  <p class="mt-3"><span class="text-2xl font-bold tabular-nums">${esc(j.cur)} ${fmt(j.salary)}</span> ${L('perMonth', 'per month', null, 'span', 'text-muted')}</p>
  <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[15px]">
    <li class="flex items-center gap-1.5">${icon('pin', 'w-5 h-5 text-muted')}${esc(j.city)}</li>
    <li class="flex items-center gap-1.5 min-w-0">${icon('bed', 'w-5 h-5 text-muted')}${housing}</li>
  </ul>
  <div class="mt-3 flex flex-wrap items-center gap-2">
    ${wc.items.length ? chip(`${L('youPay', 'You pay')}: ${esc(wc.total)}`, 'warn', 'money') : chip(`${L('youPay', 'You pay')}: ${L('nothing', 'Nothing')}`, 'ok', 'money')}
    ${m.t && opt.match !== false ? chip(L('reqMet', '{n} of {t} requirements met', m), m.n / m.t >= .75 ? 'brand' : 'neutral', m.n / m.t >= .75 ? 'check' : 'info') : ''}
  </div>
</article>`;
  }

  window.BSR = { esc, fmt, icon, L, chip, nd, workerCosts, metCount, truthCard, jobCard };
})();
