/**
 * Telegram kanalidagi (@hamkorsavdouz) narxli postlar -> saytdagi katalog
 * (npm "prebuild"; sayt har kecha qayta yig'iladi, katalog ham yangilanadi).
 *
 *   https://t.me/s/<kanal>  ->  data/kanal.json
 *                               public/kanal/<post>-480.webp, -960.webp
 *
 * Qaysi postlar olinadi (egasi 22.10: "kanaldan avtomatik"):
 *  - narxi bor (raqam + so'm/ming/oylik so'zi) — mijozlar botidagi
 *    `hs_mb_is_product_text` bilan bir xil belgi;
 *  - oxirgi KUNLAR kun ichida joylangan;
 *  - 13 va undan ortiq oylik narx bo'laklari olib tashlanadi (1-yanvardan
 *    muddatli to'lov faqat 12 oygacha). Narx qolmasa, post olinmaydi.
 *  - Narx hisoblanmaydi: postdagi qator qanday yozilgan bo'lsa, shunday
 *    ko'rsatiladi (kalkulyator kerak emas).
 *
 * Kanal ochilmasa yoki tarmoq xatosi bo'lsa — bo'sh katalog yoziladi va
 * build davom etadi (bosh sahifadagi bo'lim shunchaki ko'rinmaydi).
 */
import { mkdirSync, writeFileSync, existsSync, readdirSync, rmSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const ROOT = join(dirname(fileURLToPath(import.meta.url)), "..");
const KANAL = "hamkorsavdouz";
const KUNLAR = 30;
const SAHIFALAR = 6; // har sahifada ~20 post
const DATA = join(ROOT, "data", "kanal.json");
const IMG_DIR = join(ROOT, "public", "kanal");
const SIZES = [480, 960];

const UA = { "User-Agent": "Mozilla/5.0 (HamkorSavdo sayt katalogi)" };

const decode = (s) =>
  s
    .replace(/&quot;/g, '"')
    .replace(/&#0?39;|&apos;/g, "'")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&nbsp;/g, " ")
    .replace(/&#(\d+);/g, (_, n) => String.fromCodePoint(Number(n)))
    .replace(/&#x([0-9a-f]+);/gi, (_, n) => String.fromCodePoint(parseInt(n, 16)))
    .replace(/&amp;/g, "&");

async function fetchText(url) {
  const ctl = new AbortController();
  const t = setTimeout(() => ctl.abort(), 20000);
  try {
    const r = await fetch(url, { headers: UA, signal: ctl.signal });
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return await r.text();
  } finally {
    clearTimeout(t);
  }
}

/** t.me/s sahifasidan postlar: { id, at, text, image }. */
function parsePage(html) {
  const out = [];
  for (const part of html.split('<div class="tgme_widget_message_wrap').slice(1)) {
    const id = new RegExp(`data-post="${KANAL}/(\\d+)"`, "i").exec(part);
    if (!id) continue;
    let text = "";
    const t = /<div class="tgme_widget_message_text[^"]*"[^>]*>([\s\S]*?)<\/div>/.exec(part);
    if (t) {
      text = decode(
        t[1]
          .replace(/<br\s*\/?>/gi, "\n")
          .replace(/<i class="emoji"[^>]*><b>([\s\S]*?)<\/b><\/i>/g, "$1")
          .replace(/<[^>]+>/g, ""),
      ).trim();
    }
    const d = /<time datetime="([^"]+)"/.exec(part);
    const img = /tgme_widget_message_(?:photo_wrap|video_thumb)[^>]*background-image:url\('([^']+)'\)/.exec(part);
    out.push({ id: Number(id[1]), at: d ? d[1] : "", text, image: img ? decode(img[1]) : "" });
  }
  return out;
}

const PRICE_WORD = /(so['‘’ʻ`]?m|сўм|сум|ming|минг|oyga|ойга|oyiga|ойига|oylik|ойлик|нарх|narx|\$)/iu;
const isProductText = (t) => t.length >= 15 && /\d[\d\s.,]{2,}/u.test(t) && PRICE_WORD.test(t.toLowerCase());
/** 13+ oylik narx bo'lagi: "24 oygacha", "18 ой", "24-oy". */
const LONG_TERM = /(?<!\d)(1[3-9]|[2-9]\d)\s*-?\s*(oy|ой|oй|мес)/iu;
/** Har post oxiridagi bir xil qatorlar — kartada kerak emas. */
const BOILER = /(📍|📞|📌|☎|telegram\s*\||instagram|admin\b|oilangiz|оилангиз|ishonchli hamkor|ишончли ҳамкор|^\s*[👇👍🔥]+\s*$|\[\s*havola\s*\])/iu;
const hasLetters = (s) => (s.match(/\p{L}/gu) || []).length >= 3;
const clean = (s) => s.replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{FE0F}\u{20E3}]/gu, "").replace(/\s+/g, " ").trim();

function toItem(p) {
  const lines = p.text
    .split(/\n+/)
    .map((l) => l.trim())
    .filter((l) => l && !BOILER.test(l));
  // 13+ oylik bo'laklarni olib tashlash (gap ichida bo'lsa — butun gap).
  const kept = lines
    .map((l) =>
      l
        .split(/(?<=[.!?;])\s+/u)
        .filter((seg) => !LONG_TERM.test(seg))
        .join(" ")
        .trim(),
    )
    .filter(Boolean);
  const priceLines = kept.filter((l) => /\d[\d\s.,]{2,}/u.test(l) && PRICE_WORD.test(l.toLowerCase())).map(clean);
  if (priceLines.length === 0) return null;
  const title = clean(kept.find((l) => hasLetters(clean(l))) || "");
  if (!title) return null;
  const body = kept
    .filter((l) => clean(l) !== title && !priceLines.includes(clean(l)))
    .map(clean)
    .filter(hasLetters)
    .join(" ")
    .slice(0, 220);
  return {
    id: p.id,
    url: `https://t.me/${KANAL}/${p.id}`,
    date: p.at.slice(0, 10),
    title: title.slice(0, 90),
    prices: priceLines.slice(0, 4).map((l) => l.slice(0, 120)),
    body,
    image: "",
    _src: p.image,
  };
}

async function saveImage(item) {
  if (!item._src) return;
  try {
    const r = await fetch(item._src, { headers: UA });
    if (!r.ok) return;
    const buf = Buffer.from(await r.arrayBuffer());
    for (const size of SIZES) {
      await sharp(buf)
        // Kesilmaydi: poster/video kadri to'liq qoladi (egasi 22.10: tepasi qirqilgan edi).
        .resize(size, size * 2, { fit: "inside", withoutEnlargement: true })
        .webp({ quality: 80 })
        .toFile(join(IMG_DIR, `${item.id}-${size}.webp`));
    }
    item.image = `/kanal/${item.id}`;
  } catch (e) {
    console.warn(`kanal: ${item.id} rasmi olinmadi — ${e.message}`);
  }
}

async function main() {
  const since = Date.now() - KUNLAR * 86400 * 1000;
  const posts = new Map();
  let before = 0;
  for (let i = 0; i < SAHIFALAR; i++) {
    const html = await fetchText(`https://t.me/s/${KANAL}${before ? `?before=${before}` : ""}`);
    const page = parsePage(html);
    if (page.length === 0) break;
    for (const p of page) posts.set(p.id, p);
    const oldest = Math.min(...page.map((p) => p.id));
    const oldestAt = Math.min(...page.map((p) => Date.parse(p.at) || Date.now()));
    if (oldest <= 1 || oldest === before || oldestAt < since) break;
    before = oldest;
  }

  const items = [...posts.values()]
    .filter((p) => (Date.parse(p.at) || 0) >= since && isProductText(p.text))
    .sort((a, b) => b.id - a.id)
    .map(toItem)
    .filter(Boolean)
    .slice(0, 12);

  rmSync(IMG_DIR, { recursive: true, force: true });
  mkdirSync(IMG_DIR, { recursive: true });
  for (const it of items) await saveImage(it);
  for (const it of items) delete it._src;

  writeFileSync(DATA, JSON.stringify({ kanal: KANAL, generatedAt: new Date().toISOString(), items }, null, 2));
  console.log(`kanal: ${posts.size} ta post ko'rildi, katalogga ${items.length} tasi (oxirgi ${KUNLAR} kun, narxli)`);
}

try {
  await main();
} catch (e) {
  console.warn(`kanal: katalog yangilanmadi (${e.message}) — bo'sh katalog bilan davom etiladi`);
  mkdirSync(dirname(DATA), { recursive: true });
  if (existsSync(IMG_DIR)) for (const f of readdirSync(IMG_DIR)) rmSync(join(IMG_DIR, f));
  writeFileSync(DATA, JSON.stringify({ kanal: KANAL, generatedAt: new Date().toISOString(), items: [] }, null, 2));
}
