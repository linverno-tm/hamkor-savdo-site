/**
 * 3D oldidan va keyin: tashrif, ariza, qo'ng'iroq va Telegram bosishlari (Yandex Metrika API).
 *
 *   node tools/metrika-solishtir.mjs
 *   node tools/metrika-solishtir.mjs 2026-10-12:2026-10-25 2026-10-27:2026-11-09
 *
 * Kalit (OAuth token, faqat "metrika:read") — egasi o'zi yaratadi va faylga qo'yadi:
 *   D:\HamkorCRM-data\metrika\token.txt   (yoki METRIKA_TOKEN muhit o'zgaruvchisi)
 * Kalit chatga, repoga yoki loglarga chiqmaydi.
 *
 * Nima solishtiriladi (egasi 26.10: "3D o'zini oqlaydimi?"): 3D 26.10 kuni chiqdi,
 * shu kun hisobga olinmaydi. Ikki davr bir xil uzunlikda — kunlik o'rtacha va
 * konversiya (maqsad / tashrif), telefon va kompyuter alohida. Eslatma: 22.10 da
 * sayt dizayni ham yangilangan, shuning uchun 22–25.10 alohida qator sifatida ham
 * chiqariladi (3D'siz, lekin yangi dizayn).
 */
import { readFileSync } from "node:fs";

const COUNTER = 112743600;
const GOALS = ["lead_sent", "phone_click", "telegram_click"];
const NOM = { lead_sent: "ariza", phone_click: "qo'ng'iroq", telegram_click: "telegram" };
const DEVICE = { desktop: "kompyuter", mobile: "telefon", tablet: "planshet", tv: "TV" };

function token() {
  if (process.env.METRIKA_TOKEN) return process.env.METRIKA_TOKEN.trim();
  try {
    return readFileSync("D:/HamkorCRM-data/metrika/token.txt", "utf8").trim();
  } catch {
    return "";
  }
}

const T = token();
if (!T) {
  console.log(`Metrika kaliti yo'q. Egasi bir marta qiladi:
  1) https://oauth.yandex.ru/client/new — ilova yarating, huquq: "Яндекс.Метрика: получение статистики" (metrika:read).
  2) Ilova sahifasidagi ClientID bilan oching: https://oauth.yandex.ru/authorize?response_type=token&client_id=<ClientID>
  3) Chiqqan tokenni D:\\HamkorCRM-data\\metrika\\token.txt fayliga saqlang.`);
  process.exit(2);
}

async function api(path) {
  const r = await fetch(`https://api-metrika.yandex.net${path}`, { headers: { Authorization: `OAuth ${T}` } });
  if (!r.ok) throw new Error(`Metrika ${r.status}: ${(await r.text()).slice(0, 200)}`);
  return r.json();
}

const today = new Date();
const iso = (d) => d.toISOString().slice(0, 10);
const addDays = (s, n) => {
  const d = new Date(`${s}T00:00:00Z`);
  d.setUTCDate(d.getUTCDate() + n);
  return iso(d);
};
const [beforeArg, afterArg] = process.argv.slice(2);
const after = (afterArg || `2026-10-27:${iso(new Date(Math.min(today - 864e5, Date.parse("2026-11-09"))))}`).split(":");
const days = Math.round((Date.parse(after[1]) - Date.parse(after[0])) / 864e5) + 1;
const before = (beforeArg || `${addDays("2026-10-25", -(days - 1))}:2026-10-25`).split(":");

const { goals } = await api(`/management/v1/counter/${COUNTER}/goals`);
const ids = {};
for (const g of goals) for (const c of g.conditions || []) if (GOALS.includes(c.url)) ids[c.url] = g.id;
const used = GOALS.filter((g) => ids[g]);
if (used.length < GOALS.length) console.log(`Metrikada topilmagan maqsadlar: ${GOALS.filter((g) => !ids[g]).join(", ")}`);

async function period([d1, d2]) {
  const metrics = ["ym:s:visits", ...used.map((g) => `ym:s:goal${ids[g]}reaches`)].join(",");
  const q = `/stat/v1/data?ids=${COUNTER}&metrics=${metrics}&dimensions=ym:s:deviceCategory&date1=${d1}&date2=${d2}&accuracy=full&limit=20`;
  const res = await api(q);
  const n = Math.round((Date.parse(d2) - Date.parse(d1)) / 864e5) + 1;
  const rows = {};
  for (const r of res.data) rows[DEVICE[r.dimensions[0].id] || r.dimensions[0].name] = r.metrics;
  rows.jami = res.totals;
  return { d1, d2, n, rows };
}

const pct = (a, b) => (b ? ((a / b) * 100).toFixed(2) + "%" : "—");
function print(p) {
  console.log(`\n${p.d1} — ${p.d2} (${p.n} kun)`);
  for (const [dev, m] of Object.entries(p.rows)) {
    const v = m[0];
    const parts = used.map((g, i) => `${NOM[g]} ${m[i + 1]} (${pct(m[i + 1], v)})`);
    console.log(`  ${dev.padEnd(10)} tashrif ${String(v).padStart(5)} (${(v / p.n).toFixed(1)}/kun)  ${parts.join("  ")}`);
  }
}

const A = await period(before);
const B = await period(after);
print(A);
print(B);
// Oldingi davr dizayn yangilanishini (22.10) kesib o'tsa — yangi dizayn qismi alohida.
if (before[0] < "2026-10-22" && before[1] >= "2026-10-22") print(await period(["2026-10-22", before[1]]));

console.log("\nO'zgarish (konversiya, keyin / oldin):");
for (const dev of ["telefon", "kompyuter", "jami"]) {
  const a = A.rows[dev];
  const b = B.rows[dev];
  if (!a || !b) continue;
  const parts = used.map((g, i) => {
    const ca = a[i + 1] / a[0];
    const cb = b[i + 1] / b[0];
    return `${NOM[g]} ${ca ? ((cb / ca - 1) * 100).toFixed(0) + "%" : "—"}`;
  });
  console.log(`  ${dev.padEnd(10)} ${parts.join("  ")}   (tashrif/kun ${((b[0] / B.n / (a[0] / A.n) - 1) * 100).toFixed(0)}%)`);
}
