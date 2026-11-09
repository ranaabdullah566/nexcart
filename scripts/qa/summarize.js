// Prints a compact summary of everything in ./out (storefront-N / admin-N reports).
const fs = require('fs'), path = require('path');
const dir = path.join(__dirname, 'out');
const rows = fs.readdirSync(dir).filter((f) => /^(report|admin)-\d\.json$/.test(f)).flatMap((f) => JSON.parse(fs.readFileSync(path.join(dir, f), 'utf8')));
const count = {}; const bump = (k, n = 1) => { count[k] = (count[k] || 0) + n; };
const examples = {};
for (const r of rows) {
  bump('page loads');
  if (r.error || (r.status && r.status !== 200 && r.name !== 'notfound')) bump('bad status');
  bump('console/page errors', (r.errs || []).length); bump('failed requests', (r.fails || []).length); bump('broken images', Array.isArray(r.imgs) ? r.imgs.length : (r.imgs || 0));
  if ((r.overflow || []).length || r.pageScroll > 1) bump('pages with horizontal overflow');
  for (const a of r.axe || []) { bump('axe: ' + a.id); examples['axe: ' + a.id] = examples['axe: ' + a.id] || `${r.name} (${r.theme}/${r.vn}) ${a.ex}`; }
}
console.table(count);
for (const [k, v] of Object.entries(examples)) console.log(k.padEnd(32), '→', v);
