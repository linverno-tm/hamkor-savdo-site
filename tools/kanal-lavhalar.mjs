/**
 * Rasmiy kanaldagi (@hamkorsavdouz) mijoz lavhalari — videolar — saytdagi
 * "jonli lavhalar" lentasi uchun (npm "prebuild", har kecha yangilanadi).
 *
 *   https://t.me/s/<kanal>  ->  data/lavhalar.json
 *                               public/lavha/<post>-480.webp, -960.webp  (video muqovasi)
 *
 * Faqat RASMIY kanal: u yerdagi videolarni kompaniyaning o'zi joylagan.
 * Mijozlar guruhidagi rasm/videolarni mijozlarning o'zi qo'ygan — ular
 * ruxsatsiz saytga chiqarilmaydi (egasi bilan 22.10 kelishilgan).
 *
 * Qaysi post "lavha": video + izohida mijoz voqeasi so'zlari (mijozimiz,
 * fikrlari, yetkazib berdik, g'olib, suhbat ...). Tabrik va aksiya
 * e'lonlari chiqarib tashlanadi. Eng yangi LIMIT tasi olinadi.
 *
 * Videoning o'zi yuklanmaydi: saytda muqova turadi, bosilganda Telegram'ning
 * o'z o'yinchisi (embed) ochiladi. Kanal ochilmasa — bo'sh ro'yxat, build
 * davom etadi, lenta ko'rinmaydi.
 */
import { mkdirSync, writeFileSync, rmSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const ROOT = join(dirname(fileURLToPath(import.meta.url)), "..");
const KANAL = "hamkorsavdouz";
const SAHIFALAR = 40; // ~20 post/sahifa
const LIMIT = 10;
const DATA = join(ROOT, "data", "lavhalar.json");
const IMG_DIR = join(ROOT, "public", "lavha");
const SIZES = [480, 960];
const UA = { "User-Agent": "Mozilla/5.0 (HamkorSavdo sayt lavhalari)" };

const LAVHA =
  /(mijozimiz|мижозимиз|mijozlarimiz(ning)?\s+(fikr|o.zlarini)|mijozlarimizning fikr|fikrlari|фикрлари|samimiy fikr|yetkazib berdik|етказиб бердик|yetib bordi|етиб борди|g.olib(i|lari|imiz)?\b|ғолиб|tabriklaymiz|topshirdik|suhbat|суҳбат|yutib olgan)/iu;
/** Tabrik, shartli e'lon — lavha emas. */
const EMAS =
  /(muborak|муборак|ramazon|рамазон|hayit|ҳайит|sharti|шарти|beramiz|берамиз|boshlandi|бошланди|bo.ling|бўлинг|bo.lishingiz mumkin|бўлишингиз мумкин|xarid qiling|харид қилинг)/iu;

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
const clean = (s) =>
  s
    .replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{FE0F}\u{20E3}]/gu, "")
    .replace(/\s+/g, " ")
    .trim();
const hasLetters = (s) => (s.match(/\p{L}/gu) || []).length >= 3;

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

function parsePage(html) {
  const out = [];
  for (const part of html.split('<div class="tgme_widget_message_wrap').slice(1)) {
    const id = new RegExp(`data-post="${KANAL}/(\\d+)"`, "i").exec(part);
    if (!id) continue;
    const t = /<div class="tgme_widget_message_text[^"]*"[^>]*>([\s\S]*?)<\/div>/.exec(part);
    const text = t
      ? decode(
          t[1]
            .replace(/<br\s*\/?>/gi, "\n")
            .replace(/<i class="emoji"[^>]*><b>([\s\S]*?)<\/b><\/i>/g, "$1")
            .replace(/<[^>]+>/g, ""),
        ).trim()
      : "";
    const d = /<time datetime="([^"]+)"/.exec(part);
    const thumb = /tgme_widget_message_video_thumb[^>]*background-image:url\('([^']+)'\)/.exec(part);
    const video = part.includes("tgme_widget_message_video");
    out.push({ id: Number(id[1]), at: d ? d[1] : "", text, video, thumb: thumb ? decode(thumb[1]) : "" });
  }
  return out;
}

async function main() {
  const posts = new Map();
  let before = 0;
  for (let i = 0; i < SAHIFALAR; i++) {
    const page = parsePage(await fetchText(`https://t.me/s/${KANAL}${before ? `?before=${before}` : ""}`));
    if (page.length === 0) break;
    for (const p of page) posts.set(p.id, p);
    const oldest = Math.min(...page.map((p) => p.id));
    if (oldest <= 1 || oldest === before) break;
    before = oldest;
    const found = [...posts.values()].filter((p) => p.video && LAVHA.test(p.text) && !EMAS.test(p.text)).length;
    if (found >= LIMIT) break;
  }

  const items = [...posts.values()]
    .filter((p) => p.video && p.thumb && LAVHA.test(p.text) && !EMAS.test(p.text))
    .sort((a, b) => b.id - a.id)
    .slice(0, LIMIT)
    .map((p) => {
      const lines = p.text.split(/\n+/).map(clean).filter(hasLetters);
      return {
        id: p.id,
        url: `https://t.me/${KANAL}/${p.id}`,
        date: p.at.slice(0, 10),
        title: (lines[0] || "").slice(0, 90),
        _thumb: p.thumb,
        image: "",
      };
    })
    .filter((it) => it.title);

  rmSync(IMG_DIR, { recursive: true, force: true });
  mkdirSync(IMG_DIR, { recursive: true });
  for (const it of items) {
    try {
      const r = await fetch(it._thumb, { headers: UA });
      if (r.ok) {
        const buf = Buffer.from(await r.arrayBuffer());
        for (const size of SIZES) {
          await sharp(buf)
            // Kesilmaydi: poster/video kadri to'liq qoladi (egasi 22.10: tepasi qirqilgan edi).
            .resize(size, size * 2, { fit: "inside", withoutEnlargement: true })
            .webp({ quality: 80 })
            .toFile(join(IMG_DIR, `${it.id}-${size}.webp`));
        }
        it.image = `/lavha/${it.id}`;
      }
    } catch (e) {
      console.warn(`lavha: ${it.id} muqovasi olinmadi — ${e.message}`);
    }
    delete it._thumb;
  }
  const ready = items.filter((it) => it.image);
  writeFileSync(DATA, JSON.stringify({ kanal: KANAL, generatedAt: new Date().toISOString(), items: ready }, null, 2));
  console.log(`lavha: ${posts.size} ta post ko'rildi, ${ready.length} ta mijoz lavhasi`);
}

try {
  await main();
} catch (e) {
  console.warn(`lavha: lenta yangilanmadi (${e.message}) — bo'sh ro'yxat bilan davom etiladi`);
  rmSync(IMG_DIR, { recursive: true, force: true });
  writeFileSync(DATA, JSON.stringify({ kanal: KANAL, generatedAt: new Date().toISOString(), items: [] }, null, 2));
}
