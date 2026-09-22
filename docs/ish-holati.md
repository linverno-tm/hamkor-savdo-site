# HAMKOR SAVDO — ish holati (2026-09-22)

Yangi sessiyada davom etish uchun qisqa, lekin to'liq xulosa. Avval shuni o'qing.

## 0. Birinchi navbatda

- **Mijozlar guruhi yordamchisi** (`public/admin/_lib/mijozbot.php`) — commit qilingan, push egasi "push qil" deganda.
  Arizalar boti (@murojatlarXS_bot) ikkinchi vazifa oladi: @hamkorsavdouz_mijozlari guruhidagi "X bormi? narxi?"
  savollariga javob. Push'dan keyin egasi quyidagilarni o'zi qiladi (tartib muhim):
  1. Admin → Telegram sahifasini ochish (webhook `edited_channel_post` bilan o'zi qayta ulanadi, migratsiya 7).
  2. "Mijozlar guruhi yordamchisi" bo'limiga Claude API kalitini kiritish (console.anthropic.com), "Sinash" bilan tekshirish.
  3. "Katalogni kanaldan yangilash".
  4. Shundan KEYIN botni guruhga qo'shib, admin qilish (qo'shimcha huquqsiz). Guruh `@nom` bo'yicha o'zi "mijozlar guruhi"
     bo'ladi: unga salom ham, ariza ham yuborilmaydi. Ixtiyoriy: botni @hamkorsavdouz kanaliga ham admin qilish.
  5. Standart rejim — **Kuzatish**: bot guruhga YOZMAYDI, har bir savol va javob loyihasi xodimlar chatiga keladi.
     Bir necha kun javoblar to'g'ri bo'lsa, egasi panelda "Faol" ga o'tkazadi.
  - DIQQAT: push'dan OLDIN botni guruhga qo'shmang — eski kod guruhga "arizalar shu yerga keladi" deb yozadi.
- Qarorlar: savolni Claude (default `claude-opus-5`, `effort: low`, JSON schema, `fallbacks: "default"`) tushunadi; kalitsiz
  — oddiy so'z qidiruvi + har savol operatorga. Katalog: kanal postlari (t.me/s + webhook) va guruhdagi XODIMlarning narxli
  postlari (mijoz boshqa kanaldan uzatgani kirmaydi), faqat oxirgi 120 kun. "Operator bilan bog'lanish" → bot lichkasi →
  telefon (request_contact, faqat o'z raqami) → oddiy ariza (`source=telegram`, `page=telegram-guruh`).
- Ochiq savol: kanal/postlarda "24 oygacha", `content.json` da `installmentMonthsMax: 12` — bot faktlarni content.json dan oladi.

- **Ijara paneli** (`/ijara/`, kod: `public/ijara/`, `public/admin/_lib/ijara.php`, `_lib/xlsx.php`, migratsiya 8) —
  savdodan alohida: o'z kirish sahifasi va sessiyasi. Binolar (surat, qavatlar, Ko'cha/Boshqalar), ijarachilar
  (oylik + necha oy, shartnoma fayllari), har bir bino kassasi (kirim/chiqim, so'm/dollar, Hamkorbank kursi),
  eslatmalar (qarz, to'lov kuni, shartnoma muddati), Excel hisobot egasining shabloni bo'yicha.
  Rollar: `users.kind` = ijara_boshliq / ijara_ishchi (Foydalanuvchilar sahifasida). Qoldiq: minus — qarz, plus — avans.
  Egasi qiladi: repo'ni private qilish; boshliq va kassir akkauntlarini yaratish (parol ≥ 10 belgi).

## 1. Loyiha

- Sayt: https://hamkorsavdo.uz — Andijon viloyatidagi savdo tarmog'i (texnika, tilla, mebel, skuter; 12 oygacha muddatli to'lov; 4 filial).
- Repo: https://github.com/linverno-tm/hamkor-savdo-site, branch `main`.
- Deploy: `main` ga push → GitHub Actions → FTP (hosting ahost, cPanel). Odatda 2–3 daqiqa.
- Texnologiya: Next.js 16 (static export, `output: "export"`), React 19, Tailwind v4 (`@theme inline`). Postbuild `tools/uz-kr-build.mjs` o'zbek-kirill nusxasini `out/uz-kr/` ga yozadi. Ruscha sahifalar `app/ru/`.
- Admin panel: `public/admin/` — PHP + SQLite (migratsiyalar `_lib/db.php`, hozir 7-versiya). Kontent `data/content.json` da, panel uni GitHub'ga commit qilib nashr qiladi.
- Telegram bot: webhook `/api/telegram.php`; lid xabarlari, holat tugmalari, "Men oldim", filial rahbarlari, eslatmalar; mijozlar guruhi yordamchisi (`_lib/mijozbot.php`, migratsiya 7).
- Cron (cPanel, har 5 daqiqa): `/usr/local/bin/ea-php82 /home/hamkors4/public_html/admin/cron/vazifalar.php` — eslatmalar, kechki CSV zaxira, kunlik hisobot.

## 2. Ish qoidalari (egasi bilan kelishilgan)

- Commit xabariga `Co-Authored-By` qatori QO'SHILMAYDI (egasining xotira qoidasi).
- Push faqat egasi "push qil" deganda.
- Parol, token kiritilmaydi; tokenlar chatga yozdirilmaydi — egasi panelga o'zi kiritadi.
- Faqat tasdiqlangan faktlar: o'ylab topilgan raqam, sharh, narx saytga qo'yilmaydi.
- Javoblar o'zbek tilida.
- TSX fayllar CRLF — ko'p qatorli tahrirda `\r\n` ni normallashtirib, qaytarib yozing.
- `npx prettier` ishlatmang (loyihada yo'q, butun faylni qayta formatlaydi).
- Mahalliy sinov: `HS_DRY_RUN=1` (Telegram'ga haqiqiy so'rov ketmaydi) + `HS_CONFIG_EXTRA=<php fayl>` (`data_dir` vaqtinchalik papkaga).
- Vizual tekshiruv: brauzer paneli ko'pincha yashirin — headless Chrome ishonchliroq:
  `chrome --headless=new --user-data-dir=<tmp> --window-size=1440,11500 --virtual-time-budget=20000 --screenshot=<png> http://localhost:3000/`
  Dark rejim uchun `--blink-settings=preferredColorScheme=0`. Windows'da headless oyna 500px dan tor bo'lmaydi.

## 3. Bugun qilinganlar (mijozlar guruhi yordamchisidan tashqari hammasi push qilingan)

**Bot / admin**
- 30 daqiqalik eslatma guruhlarga ham boradi; arizani olgan odamni belgilaydi (username yoki ismi orqali havola — `claimed_uid` ustuni, migratsiya 6) va natija tugmalari bilan so'raydi; holat belgilanmaguncha 3 martagacha qayta so'raydi. Eslatmadagi tugma bosilsa — ariza yangilanadi, eslatmada "✔ Sotildi — @kim".
- Matnlar sahifasi: o'rinbosarlar (`{oy}`, `{filial}`, `{mahsulot}`, `{bepul_hudud}`, `**qalin**`) tartibli ro'yxat, har birining hozirgi qiymati bilan.
- Metrika: `accuracy=full` "too complicated" bersa — pastroq aniqlikda qayta urinish (va 876fddc da yanada mustahkam variant).

**Sayt — SEO**
- `/skuter/` va `/ru/skutery/` sahifalari (skuter surati Pexels'dan, bepul litsenziya: pexels.com/photo/15675779).
- "nasiya" so'zi muddatli to'lov sahifasi va tavsiflarga qo'shildi ("hamkor nasiya" qidiruvi bor edi).
- Yandex Webmaster: sayt tasdiqlangan (`public/yandex_ca1102bd1e7bf69f.html`), sitemap va 14 sahifa qayta aylanishga yuborilgan, region "Андижан" arizasi (7 kun ichida tekshiriladi).
- Google Search Console: sahifalar indeksga yuborilgan (/skuter, /ru/skutery, /filiallar).

**Sayt — yangi bosh sahifa dizayni**
- Header: Katalog (ochiluvchi: texnika, tilla, mebel, skuterlar) | Muddatli to'lov | Filiallar | Biz haqimizda | Aloqa | quyosh/oy tugmasi | Kirillcha | telefon. Scroll'da soya.
- Hero: "Oilangiz uchun ishonchli hamkor", 2 CTA, "Bizda yo'qmi?" sariq bloki, ixcham forma, 4 ta rasmli kategoriya kartochkasi (`components/sections/Categories.tsx` > `CategoryCards`).
- "Nega Hamkor?" (Trust.tsx): 7000+, 12 oy, 4 ta, **4,5 ★ Yandex (65 baho)** — haqiqiy, `data/site.ts > reviews`.
- Muddatli to'lov (binafsha), "3 qadamda xarid" (HowItWorks.tsx), "Bizda yo'q" bo'limi, to'liq forma (`#ariza`), Biz haqimizda (filial binolari aylanadi), "Jonli savdo maskani" (Store.tsx), filiallar + Yandex xarita (Branches.tsx, BranchMap.tsx), sharhlar bloki (Reviews.tsx), FAQ (8 ta savol), yakuniy CTA (kollaj), to'q binafsha footer.
- Filial sahifalari: ekranga yoyilgan, fonga singib ketadigan surat + CSS animatsiya. Bog' filiali suratlari qo'shildi (`public/filiallar/shahrixon-bog/`).
- Xarita: Yandex JS API 2.1, kalit `data/site.ts > yandexMapsKey` (ochiq kalit), brend belgilari (01–04), xira/binafsha fon, o'z ma'lumot kartochkasi ("Yo'l ko'rsatish").
- Dark mode: `html[data-theme="dark"]`; `<head>` skripti (layout.tsx THEME_SCRIPT) qurilma sozlamasi yoki saqlangan tanlovni qo'yadi; `components/ui/ThemeToggle.tsx`. Qoidalari `app/globals.css` oxirida.

## 4. Ochiq ishlar

**Egasining tanlovi kutilmoqda — tanqidiy tahlildagi 6 band:**
1. "Bizda yo'q mahsulot" 4 marta takrorlanadi — alohida binafsha bo'limni olib tashlash, muddatli to'lovdagi 3-kartochkani boshqa foydaga almashtirish.
2. Filial mavzusidagi 3 ketma-ket bo'limni birlashtirish (Biz haqimizda / Jonli savdo / Filiallar+xarita); "Biz haqimizda"ga zal va jamoa suratlari.
3. Katta formani yakuniy CTA bilan birlashtirish.
4. Binafshani 3 joyda qoldirish (muddatli to'lov, yakuniy CTA, footer).
5. Bo'limlar orasidagi bo'sh joyni ~25% kamaytirish (sahifa ~11 500px → ~8 000px).
6. Mayda: "3 qadam" belgilari, Telegram kartochkasi bo'shligi, tilla suratidagi yozuv, "4 TA" katta harf.

**Egasi o'zi qiladi:**
- Yandex Developer: xarita kaliti `868c338e…` ga HTTP Referer cheklovi (`hamkorsavdo.uz`); chatda yozilgan 3 ta kalitni almashtirish (yangi kalit berilsa — `data/site.ts` da almashtiriladi).
- Google Business: profilni o'ziniki qilish, 4 filialni qo'shish.
- Yandex Бизнес: sayt havolasi, Asaka va Andijon filiallari.
- Instagram bio: "24 oy" → "12 oy", sayt havolasi.
- Bog' filiali: HAMKOR yozuvi ko'rinadigan kirish/zal surati (hozirgi suratlarda "TEXNO MEBEL").

**Keyinroq (egasi "keyin qilamiz" degan):**
- Mahsulotlar katalogi — 1C'dagi "Складлардаги колдиклар (сотув нархида)" hisobotidan (519 ta: texnika 257, skuter 11, mebel 78, tilla 173). Ochiq savollar: "Асосий сотиш нархи" naqd narxmi? tilla narxi ko'rsatilsinmi? qoldiq faqat Shahrixonnikimi? Rasmlar: LG/Samsung/Midea/TCL/Indesit rasmiy saytlardan; Artel/Shivaki/Avalon — diller menejeridan so'rash. Tannarx HECH QAYERGA chiqmaydi.
- 1C API (OData) — KSB bulut xodimlari yoqishi kerak.
- Haftalik hisobot Telegram'ga, qaytgan mijoz belgisi, operator/filial reytingi, manba → sotuv hisoboti, oylik to'lov kalkulyatori.

## 5. Foydali joylar

- Filiallar va kontent: `data/content.json` (admin panel tahrirlaydi).
- Sayt faktlari: `data/site.ts`; menyu: `data/navigation.ts`; yo'nalish sahifalari matni: `data/topics.ts`.
- Bot: `public/admin/_lib/tgchats.php`, mijozlar guruhi: `public/admin/_lib/mijozbot.php`, eslatmalar/zaxira: `public/admin/_lib/tasks.php`.
- Metrika: `public/admin/_lib/metrika.php`, sahifa `public/admin/statistika.php` (hisoblagich 112743600).
- Sharh havolalari va QR: `docs/sharh-yigish.md`, `docs/sharh-qr.pdf`.
