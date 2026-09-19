/**
 * `next build` (output: "export") static HTML'ni ishlab chiqargandan keyin
 * ishga tushadi. O'zbek-lotin sahifalarni o'qib, matnini kirillga o'giradi va
 * out/uz-kr/ ostiga aynan shu tuzilishda yozadi — React komponentlarga
 * tegilmaydi, alohida "uz-kr" til yo'nalishi kerak emas.
 *
 * Nimaga tegadi: matn tugunlari, alt/aria-label/title/placeholder,
 * <title>, meta[name=description|og:title|og:description], <html lang>.
 * Nimaga tegmaydi: <script>, <style>, <noscript>, atribut qiymatlari (href, src, class),
 * raqamlar va lotin bo'lmagan matn (harflar tashqarisidagi hamma narsa).
 *
 * Ishlatish: `node tools/uz-kr-build.mjs` (npm run build dan keyin,
 * package.json'dagi "postbuild" skripti orqali avtomatik chaqiriladi).
 */
import { readFileSync, writeFileSync, mkdirSync, readdirSync, statSync } from "node:fs";
import { join, dirname, relative } from "node:path";
import { fileURLToPath } from "node:url";
import { parse } from "node-html-parser";
import { toUzbekCyrillic } from "../lib/translit.mjs";

const ROOT = dirname(fileURLToPath(import.meta.url));
const OUT_DIR = join(ROOT, "..", "out");
const TARGET_DIR = join(OUT_DIR, "uz-kr");
// noscript: parser ichini oddiy matn deb o'qiydi — o'girilsa HTML buziladi.
const SKIP_TAGS = new Set(["script", "style", "noscript"]);
const ATTRS_TO_TRANSLATE = ["alt", "aria-label", "title", "placeholder"];
/** Ichki sahifa havolasi — nuqta bo'lmasa (fayl kengaytmasi yo'q). */
const isPageHref = (href) =>
  href.startsWith("/") && !href.startsWith("/uz-kr") && !href.includes(".") && !href.startsWith("/api");

/**
 * Kirillga o'girilmaydigan bo'laklar: brend nomi, @nomlar
 * (Instagram/Telegram), veb manzillar va domenlar.
 *
 * Brend nomi — logotipdagi yozuvning o'zi. "HAMKOR SAVDO" -> "ҲАМКОР САВДО"
 * bo'lib qolsa, sahifadagi matn do'kon peshtaxtasidagi, chek va
 * ijtimoiy tarmoqdagi nom bilan mos kelmaydi. Domen va @nomlar ham shu
 * sababdan lotinda qoladi: "@ҳамкорсавдо.уз" ni mijoz qidirib topa olmaydi.
 */
const KEEP_LATIN = /(HAMKOR\s+SAVDO|@[A-Za-z0-9_.]+|https?:\/\/[^\s<>"]+|\b[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.(?:uz|com|ru|me|org|net)\b(?:\/[^\s<>"]*)?)/g;
const toCyr = (s) =>
  s
    .split(KEEP_LATIN)
    .map((part, i) => (i % 2 === 1 ? part : toUzbekCyrillic(part)))
    .join("");

/**
 * `rawText` — HTML manbadagi qochirilgan shakl (masalan "do&#x27;konda"),
 * `.text` esa hal qilingan haqiqiy belgilar ("do'konda"). Kirillga
 * o'girishdan OLDIN hal qilingan matn kerak (aks holda "&#x27;" ichidagi
 * yolg'iz "x" harfi ham "х" ga aylanib, yaroqsiz entity hosil bo'ladi).
 * Yozishda esa `&`/`</>` ni o'zimiz qayta qochiramiz — tuguннинг o'z
 * qo'yuvchisi (setter) hech narsani qochirmaydi.
 */
const escapeHtmlText = (s) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
/** `setAttribute` ham hech narsani qochirmaydi — qiymat ichida "&" yoki
    tirnoq bo'lsa, atributni yorib yuborishi mumkin. */
const escapeAttr = (s) => s.replace(/&/g, "&amp;").replace(/"/g, "&quot;");

function walkText(node) {
  // React `@{nom}` ni ikki matn tuguniga bo'ladi: "@" va "nom". Oldingi
  // tugun "@" bilan tugagan bo'lsa, bu tugun boshidagi nom ham lotinda qoladi.
  let afterAt = false;
  for (const child of node.childNodes) {
    if (child.nodeType === 3) {
      // matn tuguni
      const text = child.text;
      const handle = afterAt ? (/^[A-Za-z0-9_.]+/.exec(text) || [""])[0] : "";
      child.rawText = escapeHtmlText(handle + toCyr(text.slice(handle.length)));
      afterAt = /@$/.test(text);
      continue;
    }
    afterAt = false;
    if (child.nodeType === 1 && !SKIP_TAGS.has(child.tagName?.toLowerCase())) {
      for (const attr of ATTRS_TO_TRANSLATE) {
        const v = child.getAttribute(attr);
        if (v) child.setAttribute(attr, escapeAttr(toCyr(v)));
      }
      if (child.tagName?.toLowerCase() === "a" && !child.hasAttribute("data-lang-link")) {
        const href = child.getAttribute("href");
        if (href && isPageHref(href.split("#")[0].split("?")[0])) {
          const [path, rest] = [href.split(/([#?])/)[0], href.slice(href.split(/([#?])/)[0].length)];
          child.setAttribute("href", `/uz-kr${path}${rest}`);
        }
      }
      walkText(child);
    }
  }
}

/**
 * React gidratsiyasi (hydration) ishga tushsa, brauzerdagi client bundle
 * o'zining ichki (lotin) ma'lumotidan qayta render qilib, shu yerda
 * kirillga o'girilgan matnni ustidan bosib qo'yadi — mos kelmaslik
 * (mismatch) tufayli React DOMni "tuzatadi". Sayt JavaScript'siz to'liq
 * ishlaydigan qilib qurilgani uchun (mobil menyu — <details>, lightbox —
 * `:target`, sonlar serverda tayyor holda chiqadi) yechim oddiy: bu
 * nusxada gidratsiya skriptlarini butunlay olib tashlaymiz. JSON-LD
 * (qidiruv tizimlari uchun) qoladi — u ijro etilmaydi, xavfsiz.
 */
function stripHydrationScripts(root) {
  for (const script of root.querySelectorAll("script")) {
    const keep = script.getAttribute("type") === "application/ld+json" || script.hasAttribute("data-keep");
    if (!keep) script.remove();
  }
}

/**
 * Ikkala nusxaning qidiruv tizimiga aytadigan gapini to'g'rilaydi.
 *
 * Muammo shu edi: kirill nusxadagi `canonical` lotincha manzilga ishora
 * qilardi. Google uchun bu "meni indekslamang, asli nusxa u yerda" degani —
 * ya'ni kirill yozuvda qidiradigan mijoz saytni umuman topa olmasdi.
 * Bundan tashqari `hreflang` juftligi faqat bosh sahifada bor edi: ichki
 * sahifalar o'z `alternates.canonical` ini belgilaganda Next'ning til
 * ro'yxati ular uchun butunlay yo'qolgan.
 *
 * To'g'ri tartib: har bir nusxa o'ziga `canonical` qo'yadi, ikkalasi esa
 * bir xil `hreflang` juftligini ko'rsatadi. Shu ish bitta joyda — kirill
 * nusxani yasaydigan skriptda — bajariladi, chunki lotin/kirill manzil
 * bog'lanishini faqat shu skript biladi.
 *
 * Indekslanmaydigan sahifalar (404, rahmat) chetda qoladi: ularning
 * `canonical` yozuvi umumiy sozlamadan kelib bosh sahifaga ishora qiladi,
 * ya'ni ularni juftlashtirsak, 404 sahifa bosh sahifaning kirillcha
 * muqobili deb e'lon qilingan bo'lardi.
 *
 * @returns {{latin: string, cyrl: string} | null}
 */
function pairUrls(root) {
  const robots = root.querySelector('meta[name="robots"]');
  if (robots && /noindex/i.test(robots.getAttribute("content") || "")) return null;
  const canonical = root.querySelector('link[rel="canonical"]');
  if (!canonical) return null;
  const latin = canonical.getAttribute("href");
  if (!latin) return null;
  const u = new URL(latin);
  return { latin, cyrl: `${u.origin}/uz-kr${u.pathname}` };
}

/* Mavjud hreflang havolalari (Next faqat bosh sahifaga qo'yadi) — skript
   qayta ishga tushganda ikkilanib ketmasligi uchun avval olib tashlanadi.
   `hreflang` atributi faqat shu havolalarda uchraydi. */
const HREFLANG_TAG = /<link\b[^>]*\bhref[Ll]ang=[^>]*>/g;
const CANONICAL_TAG = /<link\b[^>]*\brel="canonical"[^>]*>/i;

/**
 * Matn ustida ishlaydi, DOM emas: lotin nusxa React tomonidan gidratsiya
 * qilinadi va undagi `<!--$-->` kabi belgilar muhim. Faylni qaytadan
 * yig'ish o'rniga faqat kerakli teglarni almashtiramiz.
 */
function withSeoLinks(html, urls, canonicalHref) {
  let out = html.replace(HREFLANG_TAG, "");
  out = out.replace(CANONICAL_TAG, (tag) =>
    tag.replace(/\bhref="[^"]*"/i, `href="${escapeAttr(canonicalHref)}"`),
  );
  const links =
    `<link rel="alternate" hreflang="uz" href="${escapeAttr(urls.latin)}"/>` +
    `<link rel="alternate" hreflang="uz-Cyrl" href="${escapeAttr(urls.cyrl)}"/>` +
    // Til aniqlanmasa lotin nusxa ko'rsatiladi — sayt asli shunda yozilgan.
    `<link rel="alternate" hreflang="x-default" href="${escapeAttr(urls.latin)}"/>`;
  return out.replace("</head>", links + "</head>");
}

function processHtml(html) {
  const root = parse(html, { comment: false });
  const titleEl = root.querySelector("title");
  if (titleEl) titleEl.set_content(escapeHtmlText(toCyr(titleEl.text)));
  for (const meta of root.querySelectorAll("meta")) {
    const name = meta.getAttribute("name") || meta.getAttribute("property");
    if (name && ["description", "og:title", "og:description", "og:site_name"].includes(name)) {
      const c = meta.getAttribute("content");
      if (c) meta.setAttribute("content", escapeAttr(toCyr(c)));
    }
  }
  const htmlEl = root.querySelector("html");
  if (htmlEl) htmlEl.setAttribute("lang", "uz-Cyrl");
  const body = root.querySelector("body");
  if (body) walkText(body);
  stripHydrationScripts(root);
  return root.toString();
}

function walkFiles(dir) {
  const out = [];
  for (const name of readdirSync(dir)) {
    const full = join(dir, name);
    if (name === "uz-kr") continue; // o'zining chiqishini qayta o'qimasin
    // Google Search Console tasdiqlash fayli — sahifa emas, bir qator matn.
    // Unga tegilmasin: bir harf o'zgarsa, sayt egaligi tasdiqlanmay qoladi.
    if (/^google[0-9a-f]+\.html$/.test(name)) continue;
    const st = statSync(full);
    if (st.isDirectory()) out.push(...walkFiles(full));
    else if (name.endsWith(".html")) out.push(full);
  }
  return out;
}

function main() {
  const files = walkFiles(OUT_DIR);
  let count = 0;
  let paired = 0;
  for (const file of files) {
    const rel = relative(OUT_DIR, file);
    const html = readFileSync(file, "utf8");
    const urls = pairUrls(parse(html, { comment: false }));

    let converted = processHtml(html);
    if (urls) {
      // Lotin asl nusxaga ham juftlik havolalari qo'shiladi — ikkalasi
      // bir-birini ko'rsatmasa, hreflang qidiruv tizimi uchun yaroqsiz.
      writeFileSync(file, withSeoLinks(html, urls, urls.latin), "utf8");
      converted = withSeoLinks(converted, urls, urls.cyrl);
      paired++;
    }

    const destPath = join(TARGET_DIR, rel);
    mkdirSync(dirname(destPath), { recursive: true });
    writeFileSync(destPath, converted, "utf8");
    count++;
  }
  console.log(`uz-kr: ${count} ta sahifa yozildi -> out/uz-kr/ (${paired} tasi hreflang juftligi bilan)`);
}

main();
