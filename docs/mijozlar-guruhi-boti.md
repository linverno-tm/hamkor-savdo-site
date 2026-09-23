# Mijozlar guruhi boti — nima qilindi (2026-09-22)

Saytdagi arizalar boti **@murojatlarXS_bot** endi ikkinchi vazifani ham bajaradi: Telegram'dagi
**@hamkorsavdouz_mijozlari** guruhida (1 528 a'zo) mijozlarning "X bormi? narxi?" degan savollariga
javob beradi. Hamma ish sayt kodida (`public/admin/`) qilingan, alohida server yoki dastur kerak emas.
Sayt hostingi 24/7 ishlaydi, shuning uchun bot kompyuter o'chiq bo'lsa ham ishlaydi.

## 1. Nega kerak bo'ldi

Guruhning oxirgi 3 oyi (~1 400 xabar) ko'rib chiqildi:

- Mijozlardan ~400 xabar, ulardan ~216 tasi savol. Deyarli hammasi bir xil ko'rinishda:
  *"X bormi? Narxi, rasmi bilan tashlab bering"*.
- Eng ko'p so'raladi: kir yuvish mashinasi, konditsioner, telefon/iPhone, planshet, muzlatgich,
  gaz plita, bolalar partasi va velosipedi, changyutgich, tilla.
- Javobni asosan 2 xodim beradi. Javob kechiksa, mijoz bir savolni qayta-qayta yozadi
  (masalan, "Marazilniklaram bormi" 11 daqiqada 5 marta yozilgan).
- Taxminan har 10 savoldan biri guruhda javobsiz qolgan (lichkada javob berilgan bo'lishi mumkin,
  shuning uchun bu taxminiy raqam).

## 2. Bot qanday ishlaydi

1. Mijoz guruhga yozadi, masalan "Skuter bormi".
2. Botning savolni Claude AI tushunadi: lotin va kirill yozuvi, xato yozilgan so'zlar ("kirmowina",
   "xaladelnik", "kandisaner"), ruscha so'zlar ("холодильник", "стиралка").
3. Bot katalogdan mos mahsulot postlarini tanlaydi va mijozning xabariga javob yozadi: qisqa izoh,
   0–3 ta post havolasi, **"📞 Operator bilan bog'lanish"** tugmasi.
4. Mijoz tugmani bossa, bot lichkasida telefon raqamini so'raydi. Raqam **oddiy ariza** bo'lib
   xodimlar chatiga tushadi: "Men oldim", holatlar va eslatmalar saytdan kelgan arizalar bilan bir xil ishlaydi.
5. Aniq javob kerak bo'lsa (narx, aniq model, limit), savol arizalar keladigan xodimlar chatiga
   **"✅ Javob berildi"** tugmasi bilan tushadi. 15 daqiqada javob bo'lmasa, bir marta eslatiladi.

### Bot nimani aytadi va nimani aytmaydi

- Mahsulot **turi** do'konning rasmiy yo'nalishlariga kirsa (maishiy texnika, skuterlar, tilla, mebel —
  `data/categories.ts`), bot "Ha, bizda … bor" deydi.
- Narx, muddat, **aniq model yoki brend** — faqat katalogdagi postda bo'lsa. Aks holda "aniq model va
  narxini operator yozib beradi".
- Do'kon shartlari (filiallar, ish vaqti, hujjatlar, muddatli to'lov, yetkazish) `data/content.json` dan
  olinadi, ya'ni saytdagi bilan bir xil.
- Bot javob **bermaydi**: guruh adminlariga (xodimlar), salom va rahmat kabi xabarlarga, bir odamning
  takroriy savoliga, boshqa kanaldan uzatilgan postlarga.

### Xodim gaplashayotganda bot aralashmaydi (2026-09-23)

Ilgari bot mijoz xabariga bir necha soniyada javob yozardi. Mijoz xodimning javobiga "Naqdgachi" deb
qaytib yozganda ham bot o'rtaga kirib umumiy gap aytdi — xodim esa aynan o'sha paytda aniq narxni
yozayotgan edi. Endi:

1. Mijoz **xodimning xabariga reply** qilsa — bu xodim bilan suhbat, bot jim.
2. Xodim shu mijozga **so'nggi 30 daqiqada** javob bergan (reply yoki `@username` bilan belgilagan)
   bo'lsa — mijozning keyingi xabarlari ham o'sha suhbat, bot jim.
3. Oddiy yangi savolga bot **darhol yozmaydi** — xodimga imkon beradi (panelda «Xodimga imkon»,
   odatda 90 soniya). Shu orada xodim javob bersa, bot umuman yozmaydi.

Xodim bilan suhbatda qolgan savolga «Javobsiz eslatma» daqiqasida hech kim javob bermasa va xodim
shu vaqtda guruhda umuman yozmagan bo'lsa, bot **guruhga yozmaydi** — xodimlar chatiga eslatma va
javob taklifini yuboradi. "Rahmat", "xo'p" kabi xabarlarga eslatma bo'lmaydi.

Xodim deb: guruh adminlari va **guruh nomidan yozgan admin** («Promoter M» kabi imzo bilan)
hisoblanadi. Kanaldan guruhga avtomatik tushgan post xodim xabari emas — unga yozilgan savolga bot
odatdagidek javob beradi.

Panelda savol holati: **«👤 xodim bilan suhbatda»**.

## 3. Rejimlar

| Rejim | Guruhda | Xodimlar chatida |
|---|---|---|
| **🧪 Kuzatish** (standart) | Bot hech narsa yozmaydi | Har bir savol + bot qanday javob berardi |
| **✅ Faol** | Bot mijozga javob yozadi | Faqat operator kerak bo'lgan savollar |

Tavsiya: bir necha kun kuzatish rejimida bot javoblarini tekshiring, to'g'ri bo'lsa Faol'ga o'ting.

## 4. Katalog (bot qaysi postlarni ko'rsatadi)

- **@hamkorsavdouz kanali**: bot kanalda admin bo'lsa, yangi va tahrirlangan postlar darhol tushadi.
  Bo'lmasa, har 6 soatda kanalning ochiq sahifasidan (t.me/s/hamkorsavdouz) yig'iladi.
- **Guruhdagi xodimlarning narxli postlari** (narx yoki oylik to'lov yozilgan post). Xodimning mijozga
  **reply** qilgan javobi ("1.087.000 tushadi 3oyga") katalogga tushmaydi: unda mahsulot nomi yo'q.
- Faqat narxi bor postlar olinadi (tabrik, mijoz fikri kabi postlar kirmaydi) va faqat oxirgi 120 kunniki.
- Mijoz boshqa do'kon kanalidan post uzatsa, u katalogga tushmaydi.

**Muhim:** kanalda asosan aksiya postlari bor, ko'pi iyun oyiniki. Alohida mahsulot kartochkalari
asosan guruhning o'zida tashlanadi, Telegram esa eski guruh xabarlarini botga bermaydi. Shu sababli
katalog birinchi kunlarda kichik bo'ladi va xodimlar yangi post tashlagan sari to'lib boradi.

## 5. Xavfsizlik

- Mijozlar guruhi va mahsulot kanaliga **ariza (mijozlar telefoni) hech qachon yuborilmaydi**: kimdir
  panelda yoki bot tugmasida adashib yoqmoqchi bo'lsa ham.
- Eski kod botni guruhga qo'shganda "arizalar shu yerga keladi" deb salom yozardi. Endi bu xabar
  mijozlar guruhiga ham, `@nomi` bor har qanday ochiq guruhga ham yozilmaydi.
- Ariza faqat mijozning o'z raqamidan tuziladi (Telegram'ning "raqamni yuborish" tugmasi). Birovning
  kontakti ariza bo'lmaydi.
- Guruhdan kelgan mijozlar bot sozlamalaridagi chatlar ro'yxatiga tushmaydi, boshqaruvchiga ham
  "ruxsat berilsinmi?" so'rovi bormaydi.
- Claude API kaliti faqat serverdagi bazada saqlanadi, sahifada qayta ko'rsatilmaydi.

## 6. Admin panel

**hamkorsavdo.uz/admin → Telegram → "Mijozlar guruhi yordamchisi"**:

- holat: bot guruhda admin'mi, katalogda nechta post, Claude ulanganmi, oxirgi 7 kun statistikasi;
- rejim (Kuzatish / Faol), mijozlar guruhi va kanal nomi, xodimlarga yuborish (faqat operator kerak
  bo'lganda / har bir savol), javobsiz eslatma vaqti;
- Claude modeli: Opus 5 (eng aniq) / Sonnet 5 (arzonroq) / Haiku 4.5 (eng arzon);
- **Claude API kaliti** maydoni;
- **"Sinab ko'rish"**: savol yozasiz, bot javobini ko'rasiz, guruhga hech narsa ketmaydi;
- **"🔄 Katalogni kanaldan yangilash"**;
- oxirgi 15 ta savol: mijoz, savol, bot javobi, holati.

Chatlar ro'yxatida har bir guruh uchun rol tanlanadi: *Xodimlar chati* / *👥 Mijozlar guruhi* /
*📣 Mahsulot kanali*.

## 7. Xarajat (taxminiy)

Har bir mijoz xabari uchun Claude'ga bitta so'rov ketadi (do'kon faktlari + katalog + xabar).

| Model | Bitta xabar | 5$ taxminan |
|---|---|---|
| Opus 5 | ~2–7 sent | ~1–2 oy |
| Sonnet 5 | ~1–3 sent | ~2–3 oy |
| Haiku 4.5 | ~0,5–1,5 sent | ~3–6 oy |

Hisob kuniga 4–5 mijoz xabari (guruhning oxirgi 3 oyi) asosida. Haqiqiy xarajat
console.anthropic.com → **Usage** bo'limida ko'rinadi. Kalit kiritilmasa, bot oddiy so'z qidiruvi
bilan ishlaydi va har bir savolni operatorga yuboradi (bepul).

## 8. Ishga tushirish (tartib bilan)

1. Push qilish (saytga chiqarish): 2–3 daqiqa.
2. Admin → **Telegram** sahifasini ochish. Bot ulanishi o'zi yangilanadi.
3. console.anthropic.com da **yangi** API kalit yaratish va panelga kiritish. Chatda ochiq yozilgan
   eski kalitni o'chirib qo'yish.
4. "Sinab ko'rish" bilan 2–3 ta savolni tekshirish (masalan "Skuter bormi", "xaladelnik narxi").
5. "Katalogni kanaldan yangilash".
6. Botni **@hamkorsavdouz_mijozlari** ga qo'shish va **admin** qilish (qo'shimcha huquqsiz).
   Ixtiyoriy: @hamkorsavdouz kanaliga ham admin qilish.
7. Sinash uchun guruhga **admin bo'lmagan** akkauntdan yozish (adminlarga bot javob bermaydi).
8. Bir necha kun kuzatish, keyin panelda **Faol** rejimni tanlash.

## 9. Holat

| Commit | Nima | Saytda |
|---|---|---|
| `41cbcdd` | Mijozlar guruhi yordamchisi (asosiy qism) | ✅ push qilingan |
| `5ae436b` | "Skuter bormi" → "Ha, bizda skuterlar bor" (rasmiy yo'nalishlar) | ⏳ push qilinmagan |
| `d845e6f` | Xolodelnik/xaladelnik/морозилка va boshqa yozilishlar | ⏳ push qilinmagan |

Tekshiruvlar:

- mahalliy test bazasida 34 ta avtomatik tekshiruv (Telegram'ga haqiqiy xabar ketmaydi, Claude javobi
  fayldan olinadi);
- so'z tanish: 39 ta haqiqiy guruh iborasi (31 tasi tanilishi, 8 tasi tanilmasligi kerak);
- admin panel sahifasi test ma'lumotlari bilan rasmga olib ko'rildi.

Haqiqiy Claude kaliti bilan hali sinalmagan, buni panelda "Sinab ko'rish" bilan qilish kerak.

## 10. Ochiq savollar

- **12 oy yoki 24 oy?** Kanal postlarida "24 oygacha muddatli to'lov", saytda (`content.json`) 12 oy.
  Bot saytdagini aytadi. Qaysi biri to'g'ri bo'lsa, bir xil qilish kerak.
- **Telefonlar.** Guruhda iPhone/Samsung kartochkalari tashlanadi, lekin saytning rasmiy
  yo'nalishlarida telefon yo'q. Shuning uchun bot "telefonlar bor" deb o'zi aytmaydi, faqat post
  bo'lsa ko'rsatadi. Telefon rasmiy yo'nalish bo'lsa, `data/categories.ts` va bot ro'yxatiga qo'shish mumkin.

## 11. Fayllar

| Fayl | O'zgarish |
|---|---|
| `public/admin/_lib/mijozbot.php` | **Yangi**: katalog, Claude, guruh xabarlari, lichkadagi ariza, eslatmalar |
| `public/admin/_lib/tgchats.php` | Webhook guruh/kanal/lichka xabarlarini yangi modulga beradi; rolli chatlarga ariza yo'q |
| `public/admin/_lib/db.php` | Migratsiya 7: `tg_chats.role`, `mb_posts`, `mb_questions`, `mb_q_msgs` |
| `public/admin/_lib/tasks.php` | Cron: javobsiz savol eslatmasi, katalogni yangilash |
| `public/admin/telegram.php` | "Mijozlar guruhi yordamchisi" bo'limi, chat roli tanlovi |
| `public/admin/assets/admin.css` | Chatlar ro'yxati joylashuvi, ko'p qatorli xabarlar |
| `docs/ish-holati.md` | Ish holati yangilandi |
