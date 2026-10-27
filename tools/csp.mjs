/**
 * Har sahifa uchun hash asosidagi Content-Security-Policy (postbuild, uz-kr dan keyin).
 *
 * Nega hash: sayt statik eksport — so'rov vaqtida server kodi yo'q, demak
 * nonce ham yo'q (Next nonce'ni faqat dinamik renderda qo'yadi). Har sahifadagi
 * ichki skriptlar (Next'ning RSC qatorlari, layout'dagi Metrika/mavzu skriptlari)
 * build vaqtida ma'lum — ularning sha256 si siyosatga yoziladi. Natija:
 * 'unsafe-inline' siz script-src, ya'ni sahifaga tushib qolgan begona
 * <script> yoki onclick ishlamaydi.
 *
 * Siyosat har papkadagi .htaccess orqali beriladi (<Files "index.html">):
 * hash'lar sahifadan sahifaga farq qiladi, bitta umumiy sarlavhaga sig'maydi.
 * Ichki papka .htaccess'i tashqisidagi `Header set` ni bosib o'tadi.
 *
 *   CSP_MODE=enforce  -> Content-Security-Policy (bloklaydi)
 *   aks holda         -> Content-Security-Policy-Report-Only (faqat hisobot)
 *
 * Avval Report-Only: buzilishlar /api/csp.php ga keladi (admin/_data/csp.log),
 * bir-ikki hafta toza tursa — CSP_MODE=enforce bilan yig'iladi.
 */
import { createHash } from "node:crypto";
import { readdirSync, readFileSync, writeFileSync, existsSync } from "node:fs";
import { join, relative } from "node:path";

const OUT = "out";
const ENFORCE = process.env.CSP_MODE === "enforce";
const HEADER = ENFORCE ? "Content-Security-Policy" : "Content-Security-Policy-Report-Only";
const MARK = "# --- CSP (tools/csp.mjs) ---";

/* Tashqi manbalar — faqat sayt haqiqatan ishlatadiganlari:
   Metrika (mc.yandex.*), filiallar xaritasi (api-maps.yandex.ru va uning
   plitka/statik serverlari), lavha oynasidagi Telegram post (t.me). */
const YANDEX = "https://mc.yandex.ru https://mc.yandex.com https://yastatic.net";
const MAPS = "https://api-maps.yandex.ru https://*.maps.yandex.net https://yandex.ru https://*.yandex.ru";

const base = [
  "default-src 'self'",
  null, // script-src — sahifaga qarab
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' data: blob: https:",
  "font-src 'self' data:",
  // blob: — three.js modeldagi teksturalarni blob URL orqali o'qiydi.
  `connect-src 'self' blob: ${YANDEX} ${MAPS}`,
  `frame-src https://t.me ${YANDEX}`,
  "worker-src 'self' blob:",
  "object-src 'none'",
  "base-uri 'self'",
  "form-action 'self'",
  // Report-Only rejimida bu ikkalasi e'tiborsiz qoldiriladi (brauzer konsolga
  // ogohlantirish yozadi); frame-ancestors baribir .htaccess'dagi umumiy CSP'da bor.
  ...(ENFORCE ? ["frame-ancestors 'self'", "upgrade-insecure-requests"] : []),
  "report-uri /api/csp.php",
];

/** Bajariladigan ichki skriptlar: src siz va JSON turidagi ma'lumot bloki emas. */
function inlineScripts(html) {
  const out = [];
  for (const m of html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)) {
    const attrs = m[1];
    if (/\bsrc\s*=/.test(attrs)) continue;
    const type = /\btype\s*=\s*"([^"]*)"/i.exec(attrs)?.[1]?.toLowerCase();
    if (type && type !== "text/javascript" && type !== "module") continue;
    out.push(m[2]);
  }
  return out;
}

const sha = (s) => `'sha256-${createHash("sha256").update(s, "utf8").digest("base64")}'`;

function policy(html) {
  const hashes = [...new Set(inlineScripts(html).map(sha))];
  const parts = [...base];
  // 'wasm-unsafe-eval': 3D modellarni ochuvchi Meshopt dekoderi WebAssembly (tools/hero3d).
  // Bu faqat WASM kompilyatsiyasiga ruxsat, JavaScript eval() ga emas.
  parts[1] = `script-src 'self' 'wasm-unsafe-eval' ${hashes.join(" ")} ${YANDEX} https://api-maps.yandex.ru https://*.maps.yandex.net`;
  return parts.join("; ");
}

/* HTML ichida inline hodisa atributlari bo'lsa — hash ularni qoplamaydi. */
const handlerAttr = /<[a-z][^>]*\son[a-z]+\s*=/i;

const dirs = new Map(); // papka -> [[fayl nomi, siyosat]]
let pages = 0;
let maxLen = 0;
const warn = [];
(function walk(dir) {
  for (const e of readdirSync(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) {
      if (e.name === "_next" || e.name === "admin" || e.name === "api") continue;
      walk(p);
    } else if (e.name.endsWith(".html")) {
      const html = readFileSync(p, "utf8");
      if (handlerAttr.test(html.replace(/<script\b[\s\S]*?<\/script>/gi, ""))) warn.push(relative(OUT, p));
      const pol = policy(html);
      maxLen = Math.max(maxLen, pol.length);
      if (!dirs.has(dir)) dirs.set(dir, []);
      dirs.get(dir).push([e.name, pol]);
      pages++;
    }
  }
})(OUT);

for (const [dir, files] of dirs) {
  const file = join(dir, ".htaccess");
  const old = existsSync(file) ? readFileSync(file, "utf8") : "";
  const keep = old.includes(MARK) ? old.slice(0, old.indexOf(MARK)) : old;
  const block = files
    .map(([name, pol]) => `<Files "${name}">\n  <IfModule mod_headers.c>\n    Header always set ${HEADER} "${pol}"\n  </IfModule>\n</Files>`)
    .join("\n");
  writeFileSync(file, `${keep.replace(/\s*$/, keep ? "\n\n" : "")}${MARK}\n${block}\n`);
}

console.log(`CSP (${ENFORCE ? "bloklaydi" : "Report-Only"}): ${pages} sahifa, ${dirs.size} papka, eng uzun siyosat ${maxLen} belgi`);
if (warn.length) {
  console.error(`DIQQAT: inline hodisa atributi bor (CSP bloklaydi): ${warn.join(", ")}`);
  process.exit(1);
}
