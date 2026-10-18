/* Sample data lifted from prototype-v1.html (JOBS, CANDS, STAGES). Every page labels it as sample.
   In the Laravel app this comes from Inertia props. */
(function () {
  const JOBS = [
    { id: 'UAE-EL-0001', title: 'Building Electrician', occ: 'Electrician', city: 'Dubai', country: 'United Arab Emirates',
      employer: 'ABC Contracting LLC', postedBy: 'Northstar Manpower (licensed agency)', agencyLicence: 'Licence RA-2026-0418 · valid to Mar 2027', mandate: 'Mandate MD-1107 · 200 workers · verified',
      salary: 2200, cur: 'AED', freq: 'month', hours: 8, days: 6, overtime: 'Paid at 1.25× basic hourly rate', contract: 24,
      accommodation: 'Provided · shared, 4 per room', food: 'AED 300 monthly allowance', transport: 'Provided to site', medical: 'Provided · employer health insurance', airfare: 'Employer pays (both ways)',
      permitPayer: 'Employer', visaPayer: 'Employer', costs: [], headcount: 200, deadline: '30 Nov 2026', exp: 5,
      skills: ['Industrial wiring', 'DB installation', 'Cable termination'], lang: 'Basic English or Hindi/Urdu',
      checks: [['Employer verified', 'ok'], ['Agency licence verified', 'ok'], ['Mandate verified', 'ok'], ['Salary employer-declared', 'neutral']],
      match: [['Electrician occupation', 1], ['5+ years experience', 1], ['GCC experience', 1], ['Passport ready', 1], ['Industrial wiring', 1], ['DB installation', 1], ['Cable termination', 0], ['Basic English', 1], ['Available within 60 days', 1], ['Trade assessment passed', 0]] },
    { id: 'PL-WH-0014', title: 'Warehouse Professional', occ: 'Warehouse operative', city: 'Poznań', country: 'Poland',
      employer: 'XYZ Logistics Sp. z o.o.', postedBy: 'XYZ Logistics (direct employer)', agencyLicence: '', mandate: 'Direct hire · no agency',
      salary: 5200, cur: 'PLN', freq: 'month', hours: 8, days: 5, overtime: 'Paid per Polish labour code', contract: 12,
      accommodation: '', food: '', transport: 'Shift bus from city centre', medical: 'National health insurance', airfare: '',
      permitPayer: 'Employer', visaPayer: 'Worker', costs: [{ item: 'Visa application fee', amount: 80, payer: 'Worker', cur: 'EUR' }, { item: 'Medical check', amount: 60, payer: 'Worker', cur: 'EUR' }],
      headcount: 75, deadline: '15 Dec 2026', exp: 1, skills: ['Forklift (UDT)', 'Picking & packing', 'Scanner use'], lang: 'Basic English',
      checks: [['Employer verified', 'ok'], ['Job under re-review after change', 'warn']],
      match: [['Warehouse or logistics role', 0], ['1+ year experience', 1], ['Forklift licence', 0], ['Basic English', 1], ['Passport ready', 1], ['Available within 60 days', 1], ['EU work history', 0], ['Picking & packing', 0]] },
    { id: 'JP-IT-0007', title: 'Industrial Technician', occ: 'Electrical technician', city: 'Toyota, Aichi', country: 'Japan',
      employer: 'Global Facilities KK', postedBy: 'Global Facilities KK (direct employer)', agencyLicence: '', mandate: 'Direct hire · Specified Skilled Worker (i) route',
      salary: 230000, cur: 'JPY', freq: 'month', hours: 8, days: 5, overtime: 'Paid at 1.25× per Japanese labour law', contract: 36,
      accommodation: 'Company dormitory · JPY 25,000 / month deducted', food: 'Not provided', transport: 'Commuting allowance', medical: 'Japanese social insurance', airfare: 'Employer pays (arrival)',
      permitPayer: 'Employer', visaPayer: 'Employer', costs: [{ item: 'Skills test fee (prometric)', amount: 3000, payer: 'Worker', cur: 'BDT' }],
      headcount: 40, deadline: '20 Jan 2027', exp: 3, skills: ['Panel wiring', 'PLC basics', 'Maintenance'], lang: 'Japanese JLPT N4',
      checks: [['Employer verified', 'ok'], ['Route documents verified', 'ok']],
      match: [['Electrical occupation', 1], ['3+ years experience', 1], ['Panel wiring', 1], ['Maintenance', 1], ['Passport ready', 1], ['JLPT N4', 0], ['Skills test passed', 0], ['Available within 90 days', 1]] },
    { id: 'SA-HV-0021', title: 'HVAC Technician', occ: 'HVAC technician', city: 'Riyadh', country: 'Saudi Arabia',
      employer: 'Al Noor Facilities Co.', postedBy: 'Northstar Manpower (licensed agency)', agencyLicence: 'Licence RA-2026-0418 · valid to Mar 2027', mandate: 'Mandate MD-1121 · under review',
      salary: 2500, cur: 'SAR', freq: 'month', hours: 8, days: 6, overtime: 'Paid at 1.5× basic hourly rate', contract: 24,
      accommodation: 'Provided', food: 'Provided (3 meals)', transport: 'Provided', medical: 'Provided', airfare: 'Employer pays (both ways)',
      permitPayer: 'Employer', visaPayer: 'Employer', costs: [], headcount: 40, deadline: '10 Dec 2026', exp: 4,
      skills: ['Split AC installation', 'Chiller maintenance', 'Refrigerant handling'], lang: 'Basic English or Arabic',
      checks: [['Employer verified', 'ok'], ['Mandate pending review', 'warn']],
      match: [['HVAC occupation', 0], ['4+ years experience', 1], ['GCC experience', 1], ['Passport ready', 1], ['Refrigerant handling', 0], ['Basic English', 1]] },
    { id: 'CZ-WE-0003', title: 'MIG/MAG Welder', occ: 'Welder', city: 'Ostrava', country: 'Czechia',
      employer: 'Moravia Steelworks s.r.o.', postedBy: 'Moravia Steelworks (direct employer)', agencyLicence: '', mandate: 'Direct hire',
      salary: 42000, cur: 'CZK', freq: 'month', hours: 8, days: 5, overtime: 'Paid per Czech labour code', contract: 12,
      accommodation: 'Hostel arranged · CZK 5,000 / month deducted', food: '', transport: '', medical: 'Public health insurance', airfare: 'Not provided',
      permitPayer: 'Employer', visaPayer: 'Worker', costs: [{ item: 'Visa application fee', amount: 2500, payer: 'Worker', cur: 'CZK' }],
      headcount: 25, deadline: '31 Dec 2026', exp: 3, skills: ['MIG/MAG 135/136', 'Blueprint reading', 'Fillet welds'], lang: 'Basic English',
      checks: [['Employer verified', 'ok']],
      match: [['Welder occupation', 0], ['3+ years experience', 1], ['ISO 9606 certificate', 0], ['Passport ready', 1], ['Basic English', 1]] }
  ];
  const STAGES = ['Applied', 'Profile reviewed', 'Qualified', 'Shortlisted', 'Interview', 'Trade assessment', 'Selected', 'Offer', 'Accepted'];
  const CANDS = [
    { id: 'c1', name: 'Rahim Uddin', occ: 'Electrician', exp: 8, from: 'Bangladesh', worked: 'Bangladesh 5y · UAE 3y', gcc: true, passport: true, ss: true, idv: true, avail: 'Immediately', langs: 'Bangla, Hindi, basic English', skills: ['Industrial wiring', 'DB installation', 'Maintenance'] },
    { id: 'c2', name: 'Karim Ahmed', occ: 'Industrial Electrician', exp: 6, from: 'Bangladesh', worked: 'Bangladesh 6y', gcc: false, passport: true, ss: true, idv: true, avail: 'In 30 days', langs: 'Bangla, basic English', skills: ['Industrial wiring', 'Cable termination'] },
    { id: 'c3', name: 'Hasan Ali', occ: 'Electrical Technician', exp: 10, from: 'Pakistan', worked: 'Pakistan 6y · Saudi Arabia 4y', gcc: true, passport: true, ss: true, idv: false, avail: 'In 60 days', langs: 'Urdu, English', skills: ['DB installation', 'Troubleshooting', 'Cable termination'] },
    { id: 'c4', name: 'Sunil Thapa', occ: 'Electrician', exp: 5, from: 'Nepal', worked: 'Nepal 3y · Qatar 2y', gcc: true, passport: false, ss: false, idv: true, avail: 'Immediately', langs: 'Nepali, Hindi', skills: ['Industrial wiring', 'Conduit bending'] },
    { id: 'c5', name: 'Arjun Kumar', occ: 'Electrician', exp: 7, from: 'India', worked: 'India 4y · Oman 3y', gcc: true, passport: true, ss: false, idv: true, avail: 'In 30 days', langs: 'Hindi, Tamil, English', skills: ['DB installation', 'Maintenance', 'Cable termination'] },
    { id: 'c6', name: 'Nimal Perera', occ: 'Electrician', exp: 4, from: 'Sri Lanka', worked: 'Sri Lanka 4y', gcc: false, passport: true, ss: true, idv: true, avail: 'In 60 days', langs: 'Sinhala, English', skills: ['Industrial wiring', 'Troubleshooting'] }
  ];
  // Display extras for the design pack: trade icon per job, match vs. UAE-EL-0001 + initials per candidate.
  const ICON = { 'UAE-EL-0001': 'zap', 'PL-WH-0014': 'box', 'JP-IT-0007': 'wrench', 'SA-HV-0021': 'fan', 'CZ-WE-0003': 'flame' };
  JOBS.forEach(j => { j.icon = ICON[j.id]; });
  const MATCH = { c1: 9, c2: 7, c3: 8, c4: 6, c5: 8, c6: 6 };
  CANDS.forEach(c => { c.match = MATCH[c.id]; c.ini = c.name.split(' ').map(w => w[0]).join('').slice(0, 2); });
  window.BS_DATA = { JOBS, STAGES, CANDS };
})();
