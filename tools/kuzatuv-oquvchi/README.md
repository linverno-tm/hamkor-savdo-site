# Kuzatuv o'quvchisi

Raqobatchilarning ochiq Telegram **guruhlarini** o'qib, yangi e'lonlarni saytdagi panelga
yuboradigan kichik dastur.

## Nega alohida dastur kerak

Telegram boti faqat **o'zi a'zo bo'lgan** chatni ko'ra oladi — bu Telegram qoidasi, kod
bilan aylanib o'tib bo'lmaydi. Raqobatchilarning do'kon guruhlari (masalan
«Imkon savdo markazi (Shahrixon filiali)») — aynan model va oylik to'lov yozilgan joy —
guruh bo'lgani uchun:

- bot orqali o'qilmaydi (biz u yerda a'zo emasmiz),
- `t.me/s/<nom>` ochiq sahifasi ham berilmaydi (Telegram guruhlarga bunday sahifa bermaydi).

Shuning uchun bu dastur **oddiy Telegram akkaunti** bilan ishlaydi: ochiq guruhga xuddi
odam kabi a'zo bo'ladi va yangi xabarlarni o'qiydi.

Ochiq kanallar (`@ishonch`, `@texnomart`, `@imkonsavdomarkazi` va h.k.) bu dasturga
muhtoj emas — ularni panelning o'zi to'g'ridan-to'g'ri oladi.

## Dastur nima qiladi va nima qilmaydi

**Qiladi:** ochiq guruhlarni o'qiydi, yangi xabar matnini (rasm ostidagi yozuv ham) saytga
yuboradi.

**Qilmaydi:** hech qayerga xabar yozmaydi, hech kimni guruhga qo'shmaydi, hech narsani
o'chirmaydi, rasm va videolarni ko'chirib olmaydi.

## Muhim ogohlantirish

Bu ish oddiy foydalanuvchi akkaunti orqali bajariladi. Telegram bunday akkauntlarni
cheklab qo'yishi mumkin. Shuning uchun:

- **alohida raqam ishlating** — shaxsiy raqamni hech qachon emas;
- dasturni tez-tez so'rov yuboradigan qilib sozlamang (`oraliq_daqiqa` ni 5 dan
  kamaytirmang);
- akkauntdan boshqa maqsadda foydalanmang.

## O'rnatish

### 1. Python

[python.org](https://www.python.org/downloads/) dan Python 3.10+ o'rnating.
O'rnatishda **"Add Python to PATH"** katagiga belgi qo'ying.

### 2. Kerakli kutubxona

```
pip install telethon
```

### 3. Telegram API kalitlari

1. [my.telegram.org](https://my.telegram.org) ga kiring — kuzatuv uchun ajratilgan
   raqam bilan.
2. **API development tools** → yangi ilova yarating (nomi ixtiyoriy, masalan
   `hamkor-kuzatuv`).
3. `api_id` va `api_hash` ni nusxa oling.

### 4. Sozlama fayli

`sozlama.ini.namuna` dan nusxa oling, nomini `sozlama.ini` qiling va to'ldiring:

- `api_id`, `api_hash` — yuqoridagi qadamdan;
- `kalit` — panel → **Raqobatchilar** → «O'quvchi dastur kaliti» dan.

### 5. Guruhlarga a'zo bo'ling

Kuzatuv akkaunti bilan Telegram'ga kiring va o'qilishi kerak bo'lgan **ochiq guruhlarga
a'zo bo'ling**. A'zo bo'lmagan guruhni dastur ham o'qiy olmaydi.

### 6. Panelda guruhlarni qo'shing

Panel → **Raqobatchilar** → kanal qo'shish maydoniga guruh nomini yozing (masalan
`shahrixon_imkon`). Panel uni guruh deb tanib oladi va shu dastur ro'yxatiga qo'shadi.

### 7. Ishga tushirish

```
python oquvchi.py
```

Birinchi ishga tushirishda Telegram telefon raqamini va SMS kodini so'raydi — ularni
**kompyuter oldidagi odam kiritadi**. Keyin `seans.session` fayli yaratiladi va boshqa
so'ralmaydi.

Windows'da qulay bo'lishi uchun `ishga-tushir.bat` faylini ikki marta bosish ham mumkin.

## Kompyuter kechasi o'chsa nima bo'ladi

Hech narsa yo'qolmaydi. Dastur har safar ishga tushganda har bir guruhdan **oxirgi
ko'rilgan xabardan keyingi** hammasini oladi. Ya'ni ertalab yoqsangiz, kechasi yozilgan
e'lonlar ham tushadi.

## Muammolar

| Belgi | Sababi |
|---|---|
| `HTTP 403 — kalitni tekshiring` | `sozlama.ini` dagi `kalit` paneldagidan farq qiladi |
| `@nom: ochilmadi` | kuzatuv akkaunti o'sha guruhga a'zo emas |
| `Telegram N soniya kutishni so'radi` | juda tez-tez so'rov; `oraliq_daqiqa` ni oshiring |
| `Panelda guruh ko'rsatilmagan` | panelda hali guruh qo'shilmagan |
