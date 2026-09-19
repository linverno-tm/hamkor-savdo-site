# Saytni hostingga joylashtirish — to'liq tartib

**Holat (2026-09-19):** domen ishlayapti, server javob beryapti,
`public_html` bo'sh.

```
hamkorsavdo.uz      → A     → 37.153.159.14   ✅
www.hamkorsavdo.uz  → CNAME → hamkorsavdo.uz  ✅
NS: dns1.ahost.uz / dns2.ahost.uz             ✅
Server: uz04.ahost.uz, cPanel foydalanuvchi hamkors4
Sayt hajmi: ~9 MB (tarif 500 MB — bemalol sig'adi)
```

Yuklash **GitHub Actions orqali avtomatik** bo'ladi: `main` ga har bir
o'zgarish (admin paneldagi «Saqlash» ham) saytni qayta yig'adi va FTP
orqali yuklaydi. Qo'lda fayl tashish kerak emas.

---

## 0. AVVAL: cPanel parolini almashtiring

Parol yozishmada ochiq yuborilgan, ya'ni u endi maxfiy emas.

```
cPanel → Preferences → Password & Security → Change Password
```

Yangi parolni **hech qayerga yozishmada yubormang**. U faqat ikki joyda
kerak bo'ladi: cPanel'ning o'zida va GitHub Secrets ichida (1-qadam).
Qolgan qadamlarni shundan keyin boshlang.

---

## 1. GitHub Secrets — yuklash kalitlarini qo'yish

```
github.com/linverno-tm/hamkor-savdo-site
  → Settings → Secrets and variables → Actions
  → New repository secret
```

Beshta secret qo'shing:

| Nomi | Qiymati |
|---|---|
| `FTP_SERVER` | `uz04.ahost.uz` |
| `FTP_USERNAME` | `hamkors4` |
| `FTP_PASSWORD` | *(0-qadamdagi yangi parol)* |
| `FTP_SERVER_DIR` | `public_html/` |
| `FTP_PROTOCOL` | `ftps` |

`FTP_SERVER_DIR` oxiridagi **`/` shart** — usiz fayllar noto'g'ri joyga
tushadi.

`FTP_PROTOCOL` nega `ftps`: oddiy `ftp` parolni ochiq matnda yuboradi.
ahost 21-portda FTPS (explicit) ni qo'llab-quvvatlaydi. Agar yuklash
`ECONNREFUSED` yoki sertifikat xatosi bilan yiqilsa, bu qiymatni `ftp`
ga o'zgartiring — lekin unda parolni tez-tez almashtirib turing.

---

## 2. Birinchi yuklash

```
GitHub → Actions → "Saytni yig'ish va hostingga yuklash" → Run workflow
```

5–10 daqiqa oladi (9 MB, FTP sekin). Tugagach `hamkorsavdo.uz` ochilishi
kerak.

⚠️ Birinchi yuklashdan keyin oxirgi qadam — «Saytni tekshirish» —
**qizil bo'ladi**. Bu kutilgan: SSL hali o'rnatilmagan va PHP kalitlari
hali serverda yo'q. 3–5-qadamlardan keyin uni qayta ishga tushirasiz.

---

## 3. PHP versiyasini tanlash

```
cPanel → MultiPHP Manager → hamkorsavdo.uz → PHP 8.2 → Apply
```

**Kamida PHP 8.0 kerak.** Kod bitta joyda `str_contains()` ishlatadi, u
PHP 8.0 da paydo bo'lgan. PHP 7.4 tanlansa, ariza formasi «500» xatosi
bilan yiqiladi va sababi loglarda ko'rinmaydi.

PHP 8.2 tavsiya qilinadi: barqaror, uzoq qo'llab-quvvatlanadi. Kodda
8.1+ ga xos hech narsa yo'q, shuning uchun 8.3 ham ishlaydi.

Tekshirish: `hamkorsavdo.uz/api/lead.php` ochilganda **`<?php` matni
ko'rinmasligi** kerak. Ko'rinsa — PHP umuman yoqilmagan.

---

## 4. SSL sertifikati

```
cPanel → SSL/TLS Status → hamkorsavdo.uz va www.hamkorsavdo.uz ni belgilang
       → Run AutoSSL
```

10–15 daqiqada Let's Encrypt sertifikati o'rnatiladi va qulf belgisi
paydo bo'ladi.

**Bu qadam 2-qadamdan keyin bajarilishi shart.** AutoSSL domen
haqiqatan sizniki ekanini tekshirish uchun saytga HTTP so'rov yuboradi;
`public_html` bo'sh bo'lsa, tekshiruv yiqiladi.

---

## 5. Kalitlar fayli — `api/secrets.php`

Bu fayl **hech qachon avtomatik yuklanmaydi**. Sababi ikkita: u git'ga
qo'shilmaydi (kalitlar ochilib ketmasin), va yuklansa serverdagi
nusxasini ustiga yozib, admin panelga kirishni yo'qotardi.

Shuning uchun bir marta qo'lda yaratiladi:

```
cPanel → File Manager → public_html/api/
  → + File → nomi: secrets.php
  → ustiga bosib → Edit
```

Ichiga quyidagini yozing:

```php
<?php
return array(
    'token'   => 'BOT_KALITI',
    'chat_id' => 'CHAT_ID',

    'admin_login' => 'LOGIN',
    'admin_hash'  => 'HASH',

    'github_token'  => '',
    'github_repo'   => 'linverno-tm/hamkor-savdo-site',
    'github_branch' => 'main',

    'metrika_token'   => '',
    'metrika_counter' => 112743600,

    'site_url' => 'https://hamkorsavdo.uz',
    'data_dir' => '/home/hamkors4/hamkor-data',
);
```

### Qiymatlarni qayerdan olasiz

**`token` va `chat_id`** — kompyuteringizdagi `public/api/secrets.php`
da allaqachon bor. Tekshirildi, ishlaydi:

```
bot:  @murojatlarXS_bot
chat: @m_tojiddinov7  (shaxsiy)
```

🔸 **Qaror kerak:** hozir arizalar sizning shaxsiy Telegramingizga
tushadi. Savdo bo'limi ko'rmaydi. To'g'ri yo'l — alohida guruh ochib,
botni unga qo'shish va `chat_id` ni o'sha guruhniki bilan almashtirish.
Guruh `chat_id` si manfiy son bo'ladi (`-100...`).

**`admin_login` va `admin_hash`** — kompyuterda yarating:

```bash
php tools/admin-parol.php
```

U `public/api/secrets.php` ni yangilaydi. Chiqqan `admin_hash` qatorini
serverdagi faylga ko'chiring. Parolning o'zi hech qayerga yozilmaydi —
faqat qaytarib bo'lmaydigan xesh saqlanadi.

**`data_dir`** — `/home/hamkors4/hamkor-data`.

Nega `public_html` dan tashqarida: arizalar bazasi (SQLite) shu yerda
yashaydi. `.htaccess` uni himoya qiladi, lekin bu xostingda nginx
Apache oldida turadi va statik faylni ba'zan `.htaccess` ni o'qimay
beradi. Hujjat ildizidan tashqarida turgan faylni esa nginx umuman
topa olmaydi — himoya qoidaga emas, joylashuvga tayanadi.

Papkani File Manager'da yarating (`/home/hamkors4/` ichida,
`public_html` yonida). Yo'l to'g'riligini File Manager yuqorisidagi
manzil satridan tekshiring — ba'zi akkauntlarda u boshqacha bo'lishi
mumkin.

**`github_token`** — ixtiyoriy. Admin paneldan saytni tahrirlash
(matn, filial, aksiya) uchun kerak. Bo'sh qoldirsangiz panel ishlaydi,
faqat «Nashr qilish» tugmasi ishlamaydi. Keyinroq qo'shsa bo'ladi:
GitHub → Settings → Developer settings → Fine-grained tokens, faqat shu
repo, `Contents: Read and write` + `Actions: Read`.

**`metrika_token`** — ixtiyoriy, paneldagi statistika uchun.

### Faylni himoyalash

Yaratgandan keyin File Manager'da `secrets.php` ni belgilab →
**Permissions** → `600` qo'ying.

---

## 6. Tekshirish

Kompyuterdan:

```bash
npm run tekshir
```

21 ta tekshiruvni bajaradi: sayt ochiladimi, SSL ishlaydimi, maxfiy
fayllar yopiqmi, kirillcha nusxa joyidami, Next'ning payload yo'llari
tuzatilyaptimi va hokazo.

Boshqa manzilni tekshirish uchun:

```bash
npm run tekshir -- http://hamkorsavdo.uz
```

Hammasi yashil bo'lsa — tayyor. Bir necha ehtimoliy xato va sababi:

| Xato | Sabab |
|---|---|
| `papkalar ro'yxati chiqdi` | fayllar `public_html/sayt/` ichiga tushgan, ildizga emas |
| `sarlavhalar yo'q` | nginx `.html` ni Apache'ga bermayapti — ahost supportga yozing |
| `MAZMUN CHIQDI` | maxfiy fayl ochiq — **darhol** hal qiling |
| `PHP manba matni qaytdi` | PHP yoqilmagan (3-qadam) |
| `sertifikat yaroqsiz` | AutoSSL hali ishlamagan (4-qadam) |

Shu skript GitHub Actions ichida ham, har yuklashdan keyin avtomatik
ishlaydi — ya'ni bir narsa buzilsa, xabar topasiz.

---

## 7. Keyingi yuklashlar

Hech narsa qilish kerak emas. `main` ga o'zgarish ketsa — yoki admin
panelda «Saqlash» bosilsa — sayt o'zi yangilanadi.

Serverda **tegilmaydigan** ikki narsa bor:

- `api/secrets.php` — kalitlar
- `admin/_data/` — arizalar bazasi (agar `data_dir` ko'chirilmagan bo'lsa)

Qolgan hamma fayl har yuklashda `out/` dagi bilan almashtiriladi.
Ya'ni serverda qo'lda qilingan o'zgarish keyingi yuklashda yo'qoladi.

---

## Tekshirib bo'lmaydigan, lekin eslab qolish kerak

**Eski `hamkor-savdo.vercel.app`.** Unda o'chirilgan telefon raqami va
«18 oygacha» yozuvi bor. Sayt ishga tushgach uni o'chiring yoki
`hamkorsavdo.uz` ga 301 bilan yo'naltiring — aks holda ikkita bir xil
sayt qidiruvda bir-birini pastga tortadi.

**«Пересоздать аккаунт»** tugmasiga hech qachon tegmang — u butun
akkauntni tozalaydi.
