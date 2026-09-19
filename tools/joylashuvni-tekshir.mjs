/**
 * Joylashtirishdan keyingi tekshiruv — saytni tashqaridan, haqiqiy
 * so'rovlar bilan sinaydi.
 *
 *   node tools/joylashuvni-tekshir.mjs
 *   node tools/joylashuvni-tekshir.mjs http://hamkorsavdo.uz   (SSL'dan oldin)
 *
 * NEGA KERAK
 * Bu xostingda (ahost.uz, cPanel + Engintron) so'rov avval **nginx** ga
 * tushadi, u esa orqadagi Apache ga uzatadi. Ba'zi sozlamalarda nginx
 * statik fayllarni (.html, .json, .txt, rasm) Apache'ga umuman bermay,
 * o'zi diskdan beradi. Shunda `.htaccess` ishlamay qoladi va:
 *
 *   - xavfsizlik sarlavhalari qo'shilmaydi,
 *   - `Require all denied` ishlamaydi — `sayt.json` ochiq qolishi mumkin,
 *   - Next.js payload yo'llarini tuzatadigan RewriteRule ishlamaydi,
 *   - kirillcha 404 o'rniga lotincha chiqadi.
 *
 * Bu brauzerda ko'zga tashlanmaydi: sayt chiroyli ochilaveradi. Shuning
 * uchun har bir qoida alohida so'rov bilan tekshiriladi.
 *
 * Chiqish kodi: 0 — hammasi joyida (ogohlantirishlar bo'lsa ham),
 *               1 — kamida bitta muhim tekshiruv yiqildi.
 */

const BASE = (process.argv[2] || "https://hamkorsavdo.uz").replace(/\/$/, "");

const OK = "✓";
const XX = "✗";
const WARN = "!";

const results = [];

/**
 * Sertifikat hali o'rnatilmagan bo'lsa, `fetch` har bir so'rovni rad etadi
 * va hisobotda yigirmata "fetch failed" chiqadi — ya'ni sayt haqida hech
 * narsa bilib bo'lmaydi. Holbuki qolgan hamma narsani tekshirish mumkin:
 * sertifikat yo'qligi bitta kamchilik, yigirmata emas.
 *
 * Shuning uchun boshida bir marta sinab ko'riladi. Xato aynan
 * sertifikatga tegishli bo'lsa, tekshirish davom etadi va bu holat
 * hisobot boshida alohida aytiladi.
 */
let sertifikatYomon = false;
try {
  await fetch(BASE + "/", { redirect: "manual" }).then((r) => r.body?.cancel());
} catch (e) {
  const kod = String(e.cause?.code || e.message);
  if (/CERT|ALT_NAME|SELF_SIGNED|TLS/i.test(kod)) {
    sertifikatYomon = kod;
    process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
  }
}

/**
 * @param {string} nom       — odam o'qiydigan tavsif
 * @param {"muhim"|"ogoh"} daraja
 * @param {() => Promise<{ok: boolean, izoh: string}>} sinov
 */
async function tekshir(nom, daraja, sinov) {
  try {
    const r = await sinov();
    results.push({ nom, daraja, ...r });
  } catch (e) {
    results.push({ nom, daraja, ok: false, izoh: `so'rov yiqildi: ${e.message}` });
  }
}

/**
 * Yo'naltirishni kuzatmaydigan so'rov — redirect'ning o'zini tekshirish uchun.
 *
 * Javob tanasi HAR DOIM o'qiladi, hatto kerak bo'lmasa ham. O'qilmay
 * qolgan tana ulanishni osib qo'yadi va Node ichidagi undici
 * `assert(!this.paused)` bilan qulab tushadi — mahalliy PHP serverida
 * aynan shunday bo'ldi. Shuning uchun bu yerda matn ham qaytariladi.
 */
async function olib(yol, init = {}) {
  const r = await fetch(BASE + yol, { redirect: "manual", ...init });
  const matn = await r.text().catch(() => "");
  return { status: r.status, headers: r.headers, matn };
}

// ---------------------------------------------------------------------------
// 1. Asosiy: sayt umuman ochiladimi
// ---------------------------------------------------------------------------

await tekshir("Bosh sahifa ochiladi", "muhim", async () => {
  const r = await fetch(BASE + "/");
  const html = await r.text();
  if (r.status !== 200) return { ok: false, izoh: `HTTP ${r.status}` };
  if (html.includes("Index of /")) {
    return { ok: false, izoh: "papkalar ro'yxati chiqdi — fayllar public_html ichiga tushmagan" };
  }
  if (!html.includes("HAMKOR SAVDO")) {
    return { ok: false, izoh: "sahifada brend nomi yo'q — boshqa sayt turibdi?" };
  }
  return { ok: true, izoh: `HTTP 200, ${(html.length / 1024).toFixed(0)} KB` };
});

/* SSL va yo'naltirish tekshiruvi faqat haqiqiy domen uchun ma'noga ega.
   Mahalliy serverda (localhost) ularni o'tkazib yuboramiz — aks holda
   har safar yolg'on xato chiqaveradi. */
const host = new URL(BASE).host;
const mahalliy = /^(localhost|127\.|\[?::1)/.test(host);

if (!mahalliy) {
  await tekshir("SSL sertifikati ishlaydi", "muhim", async () => {
    if (sertifikatYomon) {
      return {
        ok: false,
        izoh: `sertifikat yaroqsiz (${sertifikatYomon}) — cPanel > SSL/TLS Status > Run AutoSSL`,
      };
    }
    const r = await fetch(`https://${host}/`, { redirect: "manual" });
    await r.body?.cancel();
    return { ok: true, izoh: `https javob berdi (${r.status})` };
  });

  await tekshir("http → https ga yo'naltiradi", "ogoh", async () => {
    const r = await fetch(`http://${host}/`, { redirect: "manual" });
    const loc = r.headers.get("location") || "";
    if (r.status >= 300 && r.status < 400 && loc.startsWith("https://")) {
      return { ok: true, izoh: `${r.status} → ${loc}` };
    }
    return { ok: false, izoh: `yo'naltirmadi (HTTP ${r.status}) — SSL o'rnatilgach tekshiring` };
  });
}

// ---------------------------------------------------------------------------
// 2. .htaccess umuman ishlayaptimi
//    Agar bu yiqilsa, pastdagilarning ko'pi ham yiqiladi — sabab bitta.
// ---------------------------------------------------------------------------

/* Bitta sarlavha ikki marta yuborilishi mumkin: bu xostingda nginx ham,
   `.htaccess` ham `X-Content-Type-Options` qo'yadi. Node ularni vergul
   bilan qo'shib beradi ("nosniff, nosniff"), shuning uchun to'g'ridan-
   to'g'ri solishtirish yolg'on xato chiqaradi. Qiymatlar bir xil bo'lsa
   muammo yo'q — takrorni tashlab, keyin solishtiramiz. */
function yagona(headers, nom) {
  const xom = headers.get(nom);
  if (!xom) return null;
  const qismlar = [...new Set(xom.split(",").map((s) => s.trim()))];
  return { qiymat: qismlar.join(", "), takror: qismlar.length === 1 && xom.includes(",") };
}

await tekshir(".htaccess qo'llanyapti (sarlavhalar)", "muhim", async () => {
  const r = await olib("/");
  const nosniff = yagona(r.headers, "x-content-type-options");
  const ref = yagona(r.headers, "referrer-policy");
  if (nosniff?.qiymat === "nosniff" && ref) {
    const eslatma = nosniff.takror ? " (nginx ham qo'yadi — takror, zararsiz)" : "";
    return { ok: true, izoh: `nosniff + Referrer-Policy: ${ref.qiymat}${eslatma}` };
  }
  return {
    ok: false,
    izoh: `sarlavhalar yo'q (nosniff=${nosniff?.qiymat}, referrer=${ref?.qiymat}) — nginx .html ni Apache'ga bermayotgan bo'lishi mumkin`,
  };
});

await tekshir("HTML keshlanmaydi", "muhim", async () => {
  const r = await olib("/");
  const cc = r.headers.get("cache-control") || "";
  if (/must-revalidate|no-cache|max-age=0/.test(cc)) {
    return { ok: true, izoh: cc };
  }
  return { ok: false, izoh: `Cache-Control: "${cc}" — admin paneldagi o'zgarish saytda ko'rinmay qoladi` };
});

await tekshir("Statik fayllar uzoq keshlanadi", "ogoh", async () => {
  const html = await (await fetch(BASE + "/")).text();
  const m = html.match(/\/_next\/static\/[^"']+\.(?:js|css)/);
  if (!m) return { ok: false, izoh: "sahifada _next/static havolasi topilmadi" };
  const r = await olib(m[0]);
  const cc = r.headers.get("cache-control") || "";
  const yosh = Number(/max-age=(\d+)/.exec(cc)?.[1] ?? -1);
  const kun = Math.round(yosh / 86400);

  if (/immutable/.test(cc) || yosh >= 31536000) return { ok: true, izoh: cc };

  /* Bu xostingda statik fayllarning Cache-Control qiymatini nginx
     belgilaydi va `.htaccess` dagi qiymatni almashtirib yuboradi:
     css/js 30 kun, shrift 60 kun, robots.txt 1 kun, sitemap.xml 60
     soniya — hech biri bizning sozlamamiz emas. HTML va Next'ning
     `__PAGE__.txt` fayllari bundan mustasno, ularda biznikisi turadi,
     ya'ni mazmun eskirib qolmaydi.
     Fayl nomida mazmun xeshi bor, shuning uchun 30 kun xavfsiz — o'zgarsa
     nom ham o'zgaradi. Bir yil yaxshiroq bo'lardi, lekin uni bu yerdan
     o'zgartirib bo'lmaydi, shuning uchun bu xato emas. */
  if (yosh >= 2592000) {
    return { ok: true, izoh: `max-age=${yosh} (${kun} kun) — nginx belgilagan, xeshlangan fayl uchun xavfsiz` };
  }
  return {
    ok: false,
    izoh: yosh >= 0 ? `max-age=${yosh} (${kun} kun) — juda qisqa` : `Cache-Control: "${cc}"`,
  };
});

// ---------------------------------------------------------------------------
// 3. Maxfiy fayllar yopiqmi
//    Eng muhim qism. Bittasi ochiq qolsa — jiddiy muammo.
// ---------------------------------------------------------------------------

const yopiqBolishi = [
  ["/api/secrets.php", "Telegram boti kaliti va admin paroli hashi"],
  ["/api/secrets.example.php", "namuna fayl"],
  ["/api/sayt.json", "saytning butun mazmuni"],
  ["/.ftp-deploy-sync-state.json", "yuklash holati fayli"],
  ["/admin/_lib/bootstrap.php", "ichki kutubxona"],
  ["/admin/_data/hamkor.sqlite", "arizalar bazasi"],
];

for (const [yol, nima] of yopiqBolishi) {
  await tekshir(`Yopiq: ${yol}`, "muhim", async () => {
    const r = await olib(yol);
    const matn = r.matn;
    // 403/404 — ikkalasi ham maqbul. Asosiysi mazmun chiqmasin.
    if (r.status === 403 || r.status === 404) {
      return { ok: true, izoh: `HTTP ${r.status}` };
    }
    // PHP fayl ishga tushib bo'sh javob bersa ham xavfli emas, lekin
    // manba matni ko'rinsa — darhol tuzatish kerak.
    if (/<\?php|'token'|password_hash|"settings"/.test(matn)) {
      return { ok: false, izoh: `HTTP ${r.status} va MAZMUN CHIQDI — ${nima}` };
    }
    if (matn.trim() === "") {
      return { ok: true, izoh: `HTTP ${r.status}, javob bo'sh` };
    }
    return { ok: false, izoh: `HTTP ${r.status}, ${matn.length} belgi qaytdi` };
  });
}

await tekshir("Papkalar ro'yxati ko'rinmaydi", "muhim", async () => {
  const r = await olib("/filiallar/rasmlar/");
  const matn = r.matn;
  if (matn.includes("Index of")) return { ok: false, izoh: "Options -Indexes ishlamayapti" };
  return { ok: true, izoh: `HTTP ${r.status}` };
});

await tekshir("Admin panel login so'raydi", "muhim", async () => {
  const r = await fetch(BASE + "/admin/", { redirect: "manual" });
  const matn = await r.text().catch(() => "");
  const loc = r.headers.get("location") || "";
  if (loc.includes("login")) return { ok: true, izoh: `${r.status} → ${loc}` };
  if (/login|parol|kirish/i.test(matn) && !/arizalar|statistika/i.test(matn)) {
    return { ok: true, izoh: "login sahifasi chiqdi" };
  }
  if (matn.includes("Index of")) return { ok: false, izoh: "papkalar ro'yxati — index.php yuklanmagan" };
  return { ok: false, izoh: `HTTP ${r.status} — panel himoyasiz ochilgan bo'lishi mumkin` };
});

// ---------------------------------------------------------------------------
// 4. PHP ishlayaptimi
// ---------------------------------------------------------------------------

await tekshir("PHP ishlayapti (/api/lead.php)", "muhim", async () => {
  const r = await olib("/api/lead.php");
  const matn = r.matn;
  if (matn.includes("<?php")) {
    return { ok: false, izoh: "PHP manba matni qaytdi — PHP yoqilmagan! cPanel > MultiPHP Manager" };
  }
  // GET so'rovga forma javob bermasligi kerak (405 yoki yo'naltirish).
  if (r.status === 405 || r.status === 400 || (r.status >= 300 && r.status < 400)) {
    return { ok: true, izoh: `HTTP ${r.status} — GET rad etildi, to'g'ri` };
  }
  return { ok: true, izoh: `HTTP ${r.status}` };
});

// ---------------------------------------------------------------------------
// 5. Next.js va ko'p tillilik
// ---------------------------------------------------------------------------

await tekshir("Next payload yo'li tuzatiladi", "muhim", async () => {
  // Brauzer nuqtali nom bilan so'raydi, .htaccess uni papka yo'liga aylantiradi.
  const yol = "/filiallar/andijon-amir-temur/__next.filiallar.$d$slug.__PAGE__.txt";
  const r = await olib(yol);
  if (r.status === 200) return { ok: true, izoh: "HTTP 200" };
  return {
    ok: false,
    izoh: `HTTP ${r.status} — har sahifa ochilganda 404 sahifasi bekorga yuklanadi`,
  };
});

await tekshir("Kirillcha nusxa ochiladi", "muhim", async () => {
  const r = await fetch(BASE + "/uz-kr/");
  const html = await r.text();
  if (r.status !== 200) return { ok: false, izoh: `HTTP ${r.status}` };
  if (!/[Ѐ-ӿ]/.test(html)) return { ok: false, izoh: "kirill harflari yo'q" };
  return { ok: true, izoh: "HTTP 200, kirill matn bor" };
});

await tekshir("hreflang juftligi to'g'ri", "ogoh", async () => {
  const html = await (await fetch(BASE + "/")).text();
  const n = (html.match(/hreflang=/gi) || []).length;
  if (n >= 3) return { ok: true, izoh: `${n} ta hreflang` };
  return { ok: false, izoh: `atigi ${n} ta hreflang — kutilgani 3 ta` };
});

await tekshir("404 sahifasi ishlaydi", "ogoh", async () => {
  const r = await fetch(BASE + "/bunday-sahifa-yoq-12345/");
  const html = await r.text();
  if (r.status !== 404) return { ok: false, izoh: `HTTP ${r.status} (kutilgani 404)` };
  if (html.includes("Index of")) return { ok: false, izoh: "papkalar ro'yxati chiqdi" };
  return { ok: true, izoh: "HTTP 404, o'z sahifamiz" };
});

await tekshir("Kirillcha 404 kirillcha chiqadi", "ogoh", async () => {
  const r = await fetch(BASE + "/uz-kr/bunday-sahifa-yoq-12345/");
  const html = await r.text();
  if (r.status !== 404) return { ok: false, izoh: `HTTP ${r.status}` };
  if (!/[Ѐ-ӿ]/.test(html)) {
    return { ok: false, izoh: "lotincha 404 chiqdi — .htaccess dagi <If> bloki ishlamayapti" };
  }
  return { ok: true, izoh: "kirillcha 404" };
});

// ---------------------------------------------------------------------------
// 6. Qidiruv tizimlari uchun
// ---------------------------------------------------------------------------

for (const [yol, kutilgan] of [
  ["/robots.txt", /sitemap/i],
  ["/sitemap.xml", /<urlset|<loc>/i],
]) {
  await tekshir(`Mavjud: ${yol}`, "ogoh", async () => {
    const r = await fetch(BASE + yol);
    const matn = await r.text();
    if (r.status !== 200) return { ok: false, izoh: `HTTP ${r.status}` };
    if (!kutilgan.test(matn)) return { ok: false, izoh: "mazmuni kutilganidek emas" };
    return { ok: true, izoh: `HTTP 200, ${matn.length} belgi` };
  });
}

await tekshir("Sahifalar indekslanishga ochiq", "muhim", async () => {
  const r = await olib("/");
  const xr = r.headers.get("x-robots-tag") || "";
  const html = await (await fetch(BASE + "/")).text();
  const meta = html.match(/<meta[^>]*name="robots"[^>]*>/i)?.[0] || "";
  if (/noindex/i.test(xr) || /noindex/i.test(meta)) {
    return { ok: false, izoh: `noindex topildi — qidiruvga chiqmaydi (${xr || meta})` };
  }
  return { ok: true, izoh: "noindex yo'q" };
});

// ---------------------------------------------------------------------------
// Hisobot
// ---------------------------------------------------------------------------

const en = Math.max(...results.map((r) => r.nom.length));
let yiqilgan = 0;
let ogohlantirish = 0;

console.log(`\n  ${BASE}\n`);
if (sertifikatYomon) {
  console.log(`  ${WARN}  Sertifikat yaroqsiz, shuning uchun qolgan tekshiruvlar uni`);
  console.log(`     e'tiborga olmay o'tkazildi. Faqat shu saytni sinaymiz.\n`);
}
for (const r of results) {
  const belgi = r.ok ? OK : r.daraja === "muhim" ? XX : WARN;
  if (!r.ok && r.daraja === "muhim") yiqilgan++;
  if (!r.ok && r.daraja === "ogoh") ogohlantirish++;
  console.log(`  ${belgi}  ${r.nom.padEnd(en)}  ${r.izoh}`);
}

console.log();
if (yiqilgan === 0 && ogohlantirish === 0) {
  console.log(`  ${OK} Hammasi joyida — ${results.length} ta tekshiruv o'tdi.\n`);
} else {
  if (yiqilgan) console.log(`  ${XX} ${yiqilgan} ta muhim muammo.`);
  if (ogohlantirish) console.log(`  ${WARN}  ${ogohlantirish} ta ogohlantirish.`);
  console.log();
}

process.exit(yiqilgan > 0 ? 1 : 0);
