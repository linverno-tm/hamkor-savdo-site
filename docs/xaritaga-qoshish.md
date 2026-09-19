# Google Maps va Yandex xaritasiga qo'shish — to'ldirish varaqasi

**Oxirgi yangilanish: 2026-09-19.** Telefon raqamlari, muddat va ish vaqti
`data/content.json` dagi joriy ma'lumotga moslashtirildi — ilgari bu
varaqada o'chirilgan `74` raqamlari va eskirgan «24 oy» turgan edi.

Bu ish saytdan alohida va **saytni kutmaydi**. Do'konlar xaritada chiqishi
uchun domen ishlashi shart emas — faqat `Sayt` maydonini hozircha bo'sh
qoldiring (4-bandga qarang).

---

## 0. AVVAL TEKSHIRING — eng muhim qadam

Yangi karta ochishdan **oldin** Google Maps va Yandex xaritasida qidiring:

- `Hamkor Savdo Shahrixon`
- `Hamkor Savdo Asaka`
- `Hamkor Savdo Andijon`

Google va Yandex ba'zan foydalanuvchilar ma'lumotidan **o'zi karta yaratib
qo'yadi**. Agar shunday karta topilsa, yangisini ochmang — o'sha kartani
biznesga biriktiring («Bu mening biznesim» / «Стать владельцем»).

Ikkita bir xil karta paydo bo'lsa, keyin ularni birlashtirish juda qiyin va
ikkalasi ham qidiruvda pastga tushadi.

### Hozirgi holat (2026-09-19)

| Filial | Google | Yandex |
|---|---|---|
| Shahrixon — Ozodbek | ✅ karta bor, **egalik olingan** | ⚠️ karta bor, egalik olinmagan — tuzatish moderatsiyada |
| Shahrixon — Bog' | ❌ yo'q | ❌ yo'q |
| Asaka — Makro | ❌ yo'q | ❌ yo'q |
| Andijon — Amir Temur | ⚠️ karta bor, tuzatish kerak | ⚠️ karta bor, ish vaqti yo'q |

---

## 1. Kim ochishi kerak

**Do'kon egasining akkauntida ochilsin.** Keyin ijrochi «menejer» sifatida
qo'shiladi.

Sabab: karta kimning akkauntida ochilsa, o'shaniki bo'lib qoladi. Keyinchalik
egalikni o'tkazish uchun qabul qilish talab qilinadi va Google buni ba'zan
kechiktiradi. Menejerni esa bir bosishda qo'shish ham, olib tashlash ham
mumkin.

---

## 2. Ish vaqti — tasdiqlangan

Egasi tomonidan 2026-09-19 da tasdiqlandi. Saytdagi ma'lumot bilan bir xil.

| Filial | Har kuni |
|---|---|
| Shahrixon — Ozodbek | **08:00 – 18:00** |
| Shahrixon — Bog' | **08:00 – 18:00** |
| Asaka — Makro | **08:00 – 18:00** |
| Andijon — Amir Temur | **09:00 – 22:00** |

Dam olish kuni yo'q — yetti kun ishlaydi. Tushlik tanaffusi belgilanmaydi.

⚠️ Hozir xaritalarda boshqacha turibdi — tuzatish kerak:
- Yandex: hamma filialda `08:00–20:00` deb yozilgan
- Google (Shahrixon): Du–Ju va Ya `08:00–18:00`, Sha `08:00–21:00`

---

## 3. To'rtta karta — har biriga alohida

Har bir filial **alohida karta**. Bitta kartaga to'rttasini sig'dirib
bo'lmaydi.

### Nomi (hamma kartada bir xil yoziladi)

```
HAMKOR SAVDO
```

Nomga shahar yoki «mebel do'koni» qo'shmang. Google buni qoidabuzarlik deb
hisoblaydi va kartani pastga tushiradi. Shahar manzildan o'zi ko'rinadi.

### Manzil, telefon va koordinata

**1-filial — Shahrixon, Ozodbek**
```
Manzil:  Shahrixon shahar, Ozodbek savdo markazi
Telefon: +998 55 203 08 80
Koord:   40.7137304, 72.0566577
```

**2-filial — Shahrixon, Bog'**
```
Manzil:  Shahrixon shahar, Shahrixon markaziy istirohat bog'i yonida
Telefon: +998 55 202 01 01
Koord:   40.710138, 72.057308
```

**3-filial — Asaka, Makro**
```
Manzil:  Asaka shahar, Umid ko'chasi, Makro supermarketi, 2-qavat
Telefon: +998 33 232 30 55
Koord:   40.6486808, 72.2345559
```

**4-filial — Andijon, Amir Temur**
```
Manzil:  Andijon shahar, Amir Temur shoh ko'chasi, 62
Telefon: +998 33 342 08 80
Koord:   40.758604, 72.3453139
```

🔴 **`+998 74 ...` bilan boshlanadigan raqamlarni hech qayerga yozmang.**
Ular o'chirilgan. Hozir Google'dagi Shahrixon kartasida va eski
`hamkor-savdo.vercel.app` saytida aynan shu o'lik raqam turibdi.

Asaka filialida **«2-qavat» degan so'zni albatta yozing** — bino boshqa
brendning nomi bilan tanilgan, bu yozuv odamni to'g'ri joyga olib boradi.

### Ijtimoiy tarmoq havolalari

| Filial | Instagram |
|---|---|
| Shahrixon — Ozodbek | `https://www.instagram.com/hamkorsavdo.shahrixon/` |
| Shahrixon — Bog' | yo'q |
| Asaka — Makro | `https://www.instagram.com/fayzlixonadon/` |
| Andijon — Amir Temur | `https://www.instagram.com/hamkorsavdo_andijon/` |

Umumiy kanallar (hamma kartada bir xil):
```
Instagram: https://www.instagram.com/hamkorsavdo.uz/
Telegram:  https://t.me/hamkorsavdouz
Telegram (mijozlar): https://t.me/hamkorsavdouz_mijozlari
```

### Sayt

```
https://hamkorsavdo.uz
```

⚠️ **Hozir kiritmang.** Domen DNS darajasida ishlamayapti —
`docs/domen-ishlamayapti.md` ga qarang. Ishlamaydigan havola kartaning
ishonchini tushiradi va Google uni tekshirib, ogohlantirish berishi mumkin.
Domen ochilgan kuni kiritasiz.

---

## 4. Toifalar (kategoriya)

Asosiy toifa bitta bo'ladi, qolganlari qo'shimcha. Filiallar bir xil tovar
sotgani uchun hammasida bir xil qo'yiladi.

**Asosiy toifa:**
```
Maishiy texnika do'koni     (Google inglizchada: Appliance store)
```

**Qo'shimcha toifalar:**
```
Mebel do'koni               (Furniture store)
Zargarlik do'koni           (Jewelry store)
Elektronika do'koni         (Electronics store)
Mototsikl do'koni           (Motorcycle dealer)  — skuterlar uchun
```

Yandexda toifalar ruscha yoziladi:
```
Магазин бытовой техники   (asosiy)
Мебельный магазин
Ювелирный магазин
Магазин электроники
Мотосалон
```

💡 Google'ning toifa maydoni klaviatura bilan tozalanmaydi — eski matn
o'chmay, yangisi ustiga yopishib qoladi. Maydonni **sichqoncha bilan
belgilab** («×» tugmasi yoki uch marta bosib) tozalang, keyin yozing.

---

## 5. Tavsif

### Qisqa variant (Yandex, 100–150 belgi)

```
Tilla, maishiy texnika va mebel. 12 oygacha muddatli to'lov — pasport va
plastik karta kifoya. O'zbekiston bo'ylab yetkazib beramiz.
```

### To'liq variant (Google, 750 belgigacha)

```
HAMKOR SAVDO — Andijon viloyatidagi savdo do'konlari tarmog'i. Bitta
do'konda uch yo'nalish birlashgan: tilla, maishiy texnika va mebel.
7000 dan ortiq mahsulot.

12 oygacha muddatli to'lov. Rasmiylashtirish uchun pasport va plastik
kartaning o'zi kifoya — kafil ham, ma'lumotnoma ham kerak emas.

Andijon viloyatida yetkazib berish va o'rnatish bepul. O'zbekiston bo'ylab
ham yetkazib beramiz.

Do'konimizda yo'q mahsulotni ham muddatli to'lovga rasmiylashtirib beramiz —
boshqa joyda ko'rgan narsangizni ayting, biz olib beramiz.

Shahrixon (2 ta), Asaka va Andijonda 4 ta filial.
```

### Ruscha (Yandex uchun)

```
HAMKOR SAVDO — сеть магазинов в Андижанской области. Золото, бытовая
техника и мебель в одном месте. Более 7000 товаров.

Рассрочка до 12 месяцев. Для оформления достаточно паспорта и пластиковой
карты — поручитель и справка не нужны.

Бесплатная доставка и установка по Андижанской области. Доставляем по всему
Узбекистану.

Оформим в рассрочку и тот товар, которого нет у нас — скажите, что вы нашли,
и мы привезём.

4 филиала: Шахрихан (2), Асака, Андижан.
```

🔴 **Muddat hamma joyda 12 oy.** Hozir ommada uchta har xil raqam turibdi:
sayt 12, Instagram bio 24, eski vercel sayt 18. Instagram bio'larini ham
tuzatish kerak — `@hamkorsavdo.uz`, `@hamkorsavdo_andijon`,
`@hamkorsavdo.shahrixon`.

---

## 6. Xususiyatlar (atributlar)

Belgilash mumkin bo'lganlarini belgilang:

- Muddatli to'lov / рассрочка — **ha**
- Yetkazib berish / доставка — **ha**
- O'rnatish xizmati / установка — **ha**
- Karta orqali to'lov — **ha**
- Naqd to'lov — **ha**

Aravacha (nogironlar) uchun kirish bor-yo'qligini **o'zingiz tekshirib**
belgilang — bilmasangiz, belgilamang.

---

## 7. Suratlar

Loyihada tayyor suratlar bor, ular EXIF va GPS ma'lumotidan tozalangan:

```
public/filiallar/<filial-id>/
```

Har bir filialga o'z suratini yuklang — boshqa filialning surati emas:

| Filial | Papka | Tayyor surat (`-960.webp`) |
|---|---|---|
| Shahrixon — Ozodbek | `shahrixon-ozodbek/` | 10 ta |
| Shahrixon — Bog' | — | **0 ta** — suratsiz karta ochiladi, keyin qo'shiladi |
| Asaka — Makro | `asaka-umid/` | 7 ta |
| Andijon — Amir Temur | `andijon-amir-temur/` | 6 ta |

`-960.webp` bilan tugaydigan fayllarni oling — ular kattaroq.

**Muqova sifatida** har doim do'konning tashqi ko'rinishini yoki brend
belgisi ko'ringan kadrni qo'ying. Odam xaritadan kelganda binoni tanishi
kerak.

Asaka filialida muqovaga `tashqi-1` qo'yilgan — kirish binosi. Ichkaridagi
menejerlar bo'limi surati muqova uchun yaramaydi: odam binoni tanimaydi.

---

## 8. Ochilgandan keyin

**Tasdiqlash.** Google pochta orqali kod yuboradi (2 haftagacha) yoki video
so'raydi. Yandex telefon orqali tekshiradi.

⚠️ Yandex tasdiqlashi **faqat kartada yozilgan raqamga** qo'ng'iroq qiladi.
Shahrixon kartasida hozir o'chirilgan `+998 74 342 08 80` turibdi — shuning
uchun egalikni olish tuzatish moderatsiyadan o'tmaguncha imkonsiz. Avval
raqamni tuzattiring, keyin egalikni oling.

**Tasdiqlash videosi.** Google so'raydigan video — bu reklama roligi emas.
Instagram'dagi tayyor videoni yuborish **ishlamaydi** va profil bloklanishi
mumkin. Video uzluksiz bitta kadrda olinishi kerak:

1. Ko'chadan boshlang — do'kon peshtoqi va nomi ko'rinsin
2. Atrofdagi bino/ko'chani ko'rsating — manzil tasdiqlansin
3. Ichkariga kiring — tovarlar, kassa ko'rinsin
4. Xodimni yoki menejerni ko'rsating

**Sharhlar.** Xaritadagi tartibni yulduzchalar hal qiladi. Do'konga QR kod
osib qo'ying: «Bizga baho bering». Har bir mijozdan so'raladi. 4.8 yulduzli
40 ta sharh — 5.0 yulduzli 3 ta sharhdan kuchli.

**Sharhlarga javob bering.** Yomon sharhga ham. Javobsiz salbiy sharh
javob berilganidan ko'ra ko'proq zarar qiladi.

**Ma'lumotni yangilab turing.** Bayram kunlari ish vaqti o'zgarsa — kartada
ham o'zgartiring.

---

## Nima kimning zimmasida

| Ish | Kim |
|---|---|
| Mavjud kartani tekshirish | ijrochi yoki egasi |
| Akkaunt va karta ochish | **egasi** |
| Manzilni tasdiqlash (video/pochta) | **egasi** |
| Ish vaqtini aniqlash | **egasi** — ✅ bajarildi |
| Maydonlarni to'ldirish, surat yuklash | ijrochi (menejer sifatida) |
| Sharhlarga javob berish | ijrochi yoki savdo bo'limi |
