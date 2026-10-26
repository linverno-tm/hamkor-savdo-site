/**
 * Brauzer sinovi — saytning har sahifasini haqiqiy Chrome'da (yashirin
 * oynada) telefon va kompyuter o'lchamida ochib, odam ko'radigan
 * xatolarni qidiradi.
 *
 *   node tools/brauzer-sinov.mjs                       (https://hamkorsavdo.uz)
 *   node tools/brauzer-sinov.mjs http://localhost:3005
 *
 * Har sahifada:
 *   - JavaScript xatolari va konsoldagi error'lar;
 *   - yuklanmay qolgan fayllar (HTTP 4xx/5xx);
 *   - sahifa oxirigacha aylantirilgach ochilmay qolgan bo'limlar ([data-m]) —
 *     22.10 da bir kompyuterda hamma bo'lim bo'sh qolgan edi;
 *   - gorizontal toshish (sahifa ekrandan keng);
 *   - sahifa qayta chizilganda ham bo'limlar ochilishi (React sahifani
 *     qayta chizgan holatni taqlid qiladi).
 * Oxirida hamma sahifalardagi ichki havolalar bir martadan so'raladi (404 yo'qmi).
 *
 * Kerak: Google Chrome (C:/Program Files/Google/Chrome/Application/chrome.exe
 * yoki CHROME muhit o'zgaruvchisi). Qo'shimcha paket kerak emas (Node 22+).
 * Chiqish kodi: 0 — xato yo'q, 1 — kamida bitta xato.
 */
import { spawn } from "node:child_process";
import { mkdtempSync, rmSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";

const BASE = (process.argv[2] || "https://hamkorsavdo.uz").replace(/\/$/, "");
/* Lokal statik serverda .htaccess yo'q — Next'ning oldindan yuklash yo'llari
   (__next.*.txt) u yerda 404 beradi, haqiqiy xostingda esa qayta yo'naltiriladi. */
const LOCAL = /^https?:\/\/(localhost|127\.0\.0\.1)/.test(BASE);
const CHROME = process.env.CHROME || "C:/Program Files/Google/Chrome/Application/chrome.exe";
const SIZES = [
  { name: "telefon", width: 390, height: 844, mobile: true },
  { name: "kompyuter", width: 1280, height: 720, mobile: false },
];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/* Sahifalar ro'yxati — sitemap'dan, ustiga ruscha va kirillcha nusxalar. */
async function pages() {
  const xml = await (await fetch(BASE + "/sitemap.xml")).text();
  const set = new Set();
  for (const m of xml.matchAll(/<loc>([^<]+)<\/loc>/g)) set.add(new URL(m[1]).pathname);
  for (const m of xml.matchAll(/href="([^"]+)"/g)) set.add(new URL(m[1]).pathname);
  for (const p of ["/", "/ru/", "/uz-kr/", "/aloqa/", "/filiallar/"]) set.add(p);
  return [...set].sort();
}

const prof = mkdtempSync(join(tmpdir(), "hs-sinov-"));
const port = 9400 + Math.floor(Math.random() * 400);
const chrome = spawn(CHROME, ["--headless=new", `--remote-debugging-port=${port}`, `--user-data-dir=${prof}`, "--no-first-run", "--hide-scrollbars", "about:blank"], { stdio: "ignore" });

let ws;
for (let i = 0; i < 60 && !ws; i++) {
  try {
    const list = await (await fetch(`http://127.0.0.1:${port}/json`)).json();
    const p = list.find((t) => t.type === "page");
    if (p) {
      // Ulanish hodisasi o'tkazib yuborilmasin — kutish darhol boshlanadi.
      const sock = new WebSocket(p.webSocketDebuggerUrl);
      await new Promise((ok, no) => { sock.addEventListener("open", ok); sock.addEventListener("error", no); });
      ws = sock;
    }
  } catch {}
  if (!ws) await sleep(250);
}
if (!ws) { console.error("Chrome ishga tushmadi"); chrome.kill(); process.exit(1); }

let seq = 0;
const pend = new Map();
let events = [];
ws.addEventListener("message", (m) => {
  const d = JSON.parse(m.data);
  if (d.id && pend.has(d.id)) { pend.get(d.id)(d); pend.delete(d.id); return; }
  if (d.method) events.push(d);
});
const send = (method, params = {}) => new Promise((r) => { const i = ++seq; pend.set(i, r); ws.send(JSON.stringify({ id: i, method, params })); });
const ev = async (expression) => (await send("Runtime.evaluate", { expression, awaitPromise: true, returnByValue: true })).result?.result?.value;

await send("Runtime.enable");
await send("Network.enable");
await send("Page.enable");

/* Sahifa oxirigacha g'ildirak bilan aylantiriladi (odamdek), keyin tekshiriladi. */
async function scrollAll() {
  for (let k = 0; k < 120; k++) {
    await send("Input.dispatchMouseEvent", { type: "mouseWheel", x: 200, y: 300, deltaX: 0, deltaY: 400 });
    await sleep(70);
    const done = await ev("scrollY + innerHeight >= document.documentElement.scrollHeight - 4");
    if (done) break;
  }
  await sleep(1200);
}

const CHECK = `(() => {
  const vw = document.documentElement.clientWidth;
  const js = document.documentElement.classList.contains('m-js');
  const closed = js ? [...document.querySelectorAll('[data-m]:not(.m-in)')].filter((e) => e.getBoundingClientRect().height > 0).map((e) => (e.className || e.tagName).toString().slice(0, 40)) : [];
  const links = [...document.querySelectorAll('a[href]')].map((a) => a.href).filter((h) => h.startsWith(location.origin));
  return { wide: document.documentElement.scrollWidth > vw + 1 ? document.documentElement.scrollWidth : 0, closed, links };
})()`;

const REPLACE = `addEventListener('load',function(){setTimeout(function(){document.querySelectorAll('main > section').forEach(function(s){var c=s.cloneNode(true);c.querySelectorAll('.m-in').forEach(function(e){e.classList.remove('m-in')});s.replaceWith(c);});},600);});`;

const errors = [];
const allLinks = new Set();
const list = await pages();
console.log(`\n  ${BASE} — ${list.length} sahifa × ${SIZES.length} o'lcham\n`);

async function visit(path, size, replace) {
  events = [];
  await send("Emulation.setDeviceMetricsOverride", { width: size.width, height: size.height, deviceScaleFactor: 1, mobile: size.mobile });
  let scriptId = null;
  if (replace) scriptId = (await send("Page.addScriptToEvaluateOnNewDocument", { source: REPLACE })).result?.identifier;
  await send("Page.navigate", { url: BASE + path });
  await sleep(2500);
  await scrollAll();
  const r = await ev(CHECK);
  if (scriptId) await send("Page.removeScriptToEvaluateOnNewDocument", { identifier: scriptId });
  const out = [];
  for (const e of events) {
    if (e.method === "Runtime.exceptionThrown") out.push("JS xato: " + (e.params.exceptionDetails.exception?.description || e.params.exceptionDetails.text).split("\n")[0].slice(0, 160));
    if (e.method === "Runtime.consoleAPICalled" && e.params.type === "error") out.push("konsol: " + JSON.stringify(e.params.args.map((a) => a.value ?? a.description)).slice(0, 160));
    if (e.method === "Network.responseReceived" && e.params.response.status >= 400 && !/mc\.yandex|metrika/.test(e.params.response.url) && !(LOCAL && /\/__next\./.test(e.params.response.url))) out.push(`HTTP ${e.params.response.status}: ${e.params.response.url}`);
  }
  if (r?.wide) out.push(`sahifa ekrandan keng: ${r.wide}px > ${size.width}px`);
  if (r?.closed?.length) out.push(`ochilmagan bo'limlar (${r.closed.length}): ${r.closed.slice(0, 4).join(" | ")}`);
  for (const l of r?.links || []) allLinks.add(l.split("#")[0]);
  return out;
}

for (const path of list) {
  for (const size of SIZES) {
    const out = await visit(path, size, false);
    const tag = `${path} [${size.name}]`;
    console.log(`  ${out.length ? "✗" : "✓"}  ${tag}`);
    for (const o of out) { console.log(`       ${o}`); errors.push(`${tag}: ${o}`); }
  }
}

/* Qayta chizilish sinovi — faqat React'li asosiy sahifalarda. */
for (const path of ["/", "/filiallar/", "/tilla/"]) {
  const out = (await visit(path, SIZES[1], true)).filter((o) => o.startsWith("ochilmagan"));
  console.log(`  ${out.length ? "✗" : "✓"}  ${path} [qayta chizilganda]`);
  for (const o of out) { console.log(`       ${o}`); errors.push(`${path} qayta chizilganda: ${o}`); }
}

console.log(`\n  Ichki havolalar: ${allLinks.size} ta tekshirilmoqda...`);
for (const l of allLinks) {
  if (/\.(pdf|jpg|jpeg|png|webp|avif|mp4)$/i.test(l) || l.includes("/admin")) continue;
  try {
    const res = await fetch(l, { redirect: "follow" });
    await res.arrayBuffer().catch(() => null); // javobni oxirigacha o'qiymiz — aks holda undici yiqiladi
    if (res.status >= 400) { console.log(`  ✗  ${res.status} ${l}`); errors.push(`havola ${res.status}: ${l}`); }
  } catch { errors.push(`havola ochilmadi: ${l}`); }
}

ws.close();
chrome.kill();
await sleep(500);
try { rmSync(prof, { recursive: true, force: true }); } catch {}
console.log(errors.length ? `\n  ✗ ${errors.length} ta xato topildi.\n` : `\n  ✓ Hammasi joyida.\n`);
process.exit(errors.length ? 1 : 0);
