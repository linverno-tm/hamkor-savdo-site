/**
 * `next build` (output: "export") static HTML'ni ishlab chiqargandan keyin
 * ishga tushadi. O'zbek-lotin sahifalarni o'qib, matnini kirillga o'giradi va
 * out/uz-kr/ ostiga aynan shu tuzilishda yozadi — React komponentlarga
 * tegilmaydi, alohida "uz-kr" til yo'nalishi kerak emas.
 *
 * Nimaga tegadi: matn tugunlari, alt/aria-label/title/placeholder,
 * <title>, meta[name=description|og:title|og:description], <html lang>.
 * Nimaga tegmaydi: <script>, <style>, atribut qiymatlari (href, src, class),
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
const SKIP_TAGS = new Set(["script", "style"]);
const ATTRS_TO_TRANSLATE = ["alt", "aria-label", "title", "placeholder"];
/** Ichki sahifa havolasi — nuqta bo'lmasa (fayl kengaytmasi yo'q). */
const isPageHref = (href) =>
  href.startsWith("/") && !href.startsWith("/uz-kr") && !href.includes(".") && !href.startsWith("/api");

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
  for (const child of node.childNodes) {
    if (child.nodeType === 3) {
      // matn tuguni
      child.rawText = escapeHtmlText(toUzbekCyrillic(child.text));
    } else if (child.nodeType === 1 && !SKIP_TAGS.has(child.tagName?.toLowerCase())) {
      for (const attr of ATTRS_TO_TRANSLATE) {
        const v = child.getAttribute(attr);
        if (v) child.setAttribute(attr, escapeAttr(toUzbekCyrillic(v)));
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

function processHtml(html) {
  const root = parse(html, { comment: false });
  const titleEl = root.querySelector("title");
  if (titleEl) titleEl.set_content(escapeHtmlText(toUzbekCyrillic(titleEl.text)));
  for (const meta of root.querySelectorAll("meta")) {
    const name = meta.getAttribute("name") || meta.getAttribute("property");
    if (name && ["description", "og:title", "og:description", "og:site_name"].includes(name)) {
      const c = meta.getAttribute("content");
      if (c) meta.setAttribute("content", escapeAttr(toUzbekCyrillic(c)));
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
    const st = statSync(full);
    if (st.isDirectory()) out.push(...walkFiles(full));
    else if (name.endsWith(".html")) out.push(full);
  }
  return out;
}

function main() {
  const files = walkFiles(OUT_DIR);
  let count = 0;
  for (const file of files) {
    const rel = relative(OUT_DIR, file);
    const html = readFileSync(file, "utf8");
    const converted = processHtml(html);
    const destPath = join(TARGET_DIR, rel);
    mkdirSync(dirname(destPath), { recursive: true });
    writeFileSync(destPath, converted, "utf8");
    count++;
  }
  console.log(`uz-kr: ${count} ta sahifa yozildi -> out/uz-kr/`);
}

main();
