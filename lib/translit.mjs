/**
 * O'zbek lotin -> kirill harf almashtirish.
 *
 * Faqat harflarga ta'sir qiladi (raqam, telefon, URL, email o'zgarmaydi).
 * Ko'p harfli birikmalar (sh, ch, o', g', ng, yo, yu, ya) eng uzunidan
 * boshlab almashtiriladi, aks holda masalan "sh" dan oldin "s" ishlanib
 * ketardi.
 *
 * .mjs (TypeScript emas) qilib saqlangan — buni ham Next.js ilovasi, ham
 * tools/uz-kr-build.mjs (oddiy Node skripti, build tugagach ishga tushadi)
 * hech qanday qo'shimcha vosita (ts-node va h.k.) kerak bo'lmasdan bevosita
 * import qila oladi.
 */

// Tartib muhim: uzunroq birikmalar avval kelishi kerak.
const DIGRAPHS = [
  ["yo'", "йў"],
  ["sh", "ш"],
  ["ch", "ч"],
  ["ng", "нг"],
  ["yo", "ё"],
  ["yu", "ю"],
  ["ya", "я"],
  // "ye" -> "е" (harfma-harf "йе" emas): yetkazish -> етказиш, yer -> ер,
  // poyezd -> поезд. Kirill "е" harfi o'zi "ye" tovushini beradi.
  ["ye", "е"],
  ["o'", "ў"],
  ["g'", "ғ"],
];

const SINGLE = {
  a: "а",
  b: "б",
  d: "д",
  e: "е",
  f: "ф",
  g: "г",
  h: "ҳ",
  i: "и",
  j: "ж",
  k: "к",
  l: "л",
  m: "м",
  n: "н",
  o: "о",
  p: "п",
  q: "қ",
  r: "р",
  s: "с",
  t: "т",
  u: "у",
  v: "в",
  x: "х",
  y: "й",
  z: "з",
};

/** Berilgan lotin harfi katta ekanini saqlab, kirill mos harfini qaytaradi. */
function matchCase(latin, cyrillic) {
  if (latin.length === 0) return cyrillic;
  const firstIsUpper = latin[0] === latin[0].toUpperCase() && latin[0] !== latin[0].toLowerCase();
  const allUpper = latin === latin.toUpperCase() && latin !== latin.toLowerCase();
  if (allUpper && latin.length > 1) return cyrillic.toUpperCase();
  if (firstIsUpper) return cyrillic[0].toUpperCase() + cyrillic.slice(1);
  return cyrillic;
}

const WORD_RE = /[A-Za-zʻʼ'’]+/g;

function transliterateWord(word) {
  // Apostrof turlarini birlashtiramiz: ʻ ʼ ' ’ -> '
  const norm = word.replace(/[ʻʼ’]/g, "'");
  let out = "";
  let i = 0;
  const lower = norm.toLowerCase();
  while (i < norm.length) {
    let matched = false;
    for (const [lat, cyr] of DIGRAPHS) {
      if (lower.startsWith(lat, i)) {
        out += matchCase(norm.slice(i, i + lat.length), cyr);
        i += lat.length;
        matched = true;
        break;
      }
    }
    if (matched) continue;
    const ch = norm[i];
    const lowerCh = lower[i];
    if (lowerCh === "e" && i === 0) {
      // So'z boshidagi "e" -> "э" (eng -> энг, e'lon -> эълон). So'z ichida
      // esa oddiy "е" bo'ladi (bepul -> бепул, mebel -> мебел).
      out += matchCase(ch, "э");
    } else if (SINGLE[lowerCh]) {
      out += matchCase(ch, SINGLE[lowerCh]);
    } else if (ch === "'") {
      // o'/g'/yo' digraflariga tushmagan yolg'iz apostrof — arabcha/forscha
      // o'zlashma so'zlardagi ayn/hamza belgisi (ma'no, san'at, e'lon).
      // Bunday holatda kirillda qattiq belgi (ъ) ishlatiladi.
      out += "ъ";
    } else {
      out += ch;
    }
    i++;
  }
  return out;
}

/**
 * Matndagi lotin so'zlarni kirillga o'giradi. Raqamlar, tinish belgilari,
 * bo'sh joy va allaqachon kirill/boshqa yozuvdagi matn tegilmaydi.
 * @param {string} text
 * @returns {string}
 */
export function toUzbekCyrillic(text) {
  return text.replace(WORD_RE, transliterateWord);
}
