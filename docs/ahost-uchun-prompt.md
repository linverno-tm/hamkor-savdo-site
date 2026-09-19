# ahost.uz tekshiruvi — Chrome'dagi Claude uchun prompt

Boshliqning kompyuterida Chrome + Claude kengaytmasi bo'lsa, quyidagi
matnni o'sha Claude'ga yuborish kifoya. Boshliqning yagona ishi —
ahost.uz ga **o'zi kirish** (parolni Claude kiritmaydi va kiritmasligi
kerak).

Muammoning texnik tafsiloti: `docs/domen-ishlamayapti.md`.

---

## 1-prompt — tekshirish (avval shuni yuboring)

Bu prompt hech narsani o'zgartirmaydi, faqat holatni aniqlaydi.

```
Salom. Menga ahost.uz xostingidagi bitta domen bilan bog'liq muammoni
aniqlashda yordam ber. O'zbek tilida javob ber.

MUAMMO
`hamkorsavdo.uz` domeni dunyoning hech bir joyidan ochilmayapti.
Tashqaridan tekshirganda shunday chiqdi:

- Domen `.uz` reyestrida ro'yxatdan o'tgan va ahost.uz nom serveriga
  yo'naltirilgan — ya'ni delegatsiya joyida.
- Lekin `uz02.ahost.uz` (IP: 37.153.159.12) bu domen haqida so'ralganda
  REFUSED javobini qaytaryapti.
- Cloudflare resolveri buni shunday izohlaydi:
  "No Reachable Authority at delegation hamkorsavdo.uz"

Bu odatda bitta narsani bildiradi: **xosting panelida shu domen uchun DNS
zonasi yaratilmagan** (yoki o'chirib yuborilgan, yoki xosting tarifi
faol emas).

SENING VAZIFANG
Men ahost.uz shaxsiy kabinetiga o'zim kiraman. Sen kirgandan keyin
panelni ko'rib chiqib, quyidagilarni aniqla va menga hisobot ber:

1. Xosting tarifi faolmi? Muddati qachon tugaydi? To'lov qarzi bormi?
2. "Домены" / "Domains" bo'limida `hamkorsavdo.uz` bormi? Qanday holatda
   turibdi (active / suspended / expired)?
3. Domenning NS (nom server) sozlamasi nima? Qaysi serverlar yozilgan?
4. DNS zonasi bormi? ("DNS", "Зона DNS", "DNS-записи" kabi bo'lim)
   - Agar bo'lsa — ichidagi barcha yozuvlarni (A, CNAME, MX, TXT) menga
     ro'yxat qilib ber.
   - Agar bo'lmasa — shuni aniq ayt.
5. `hamkorsavdo.uz` uchun A yozuvi bormi? Qaysi IP ga ishora qilyapti?
6. `www.hamkorsavdo.uz` uchun yozuv bormi?
7. Xosting serverining IP manzili nechchi? (odatda panelning bosh
   sahifasida yoki "Информация о сервере" da bo'ladi)
8. Fayl menejerida `public_html` (yoki `www`, `htdocs`) papkasi bormi va
   ichida fayllar bormi? Bo'sh bo'lsa ham ayt.
9. PHP versiyasi nechchi? SSL sertifikat bormi?

QOIDALAR — buzma
- Parol, SMS kod yoki to'lov ma'lumotini SEN kiritma. Kerak bo'lsa
  menga ayt, o'zim kiritaman.
- Hozircha HECH NARSANI o'zgartirma, o'chirma, qo'shma yoki to'lama.
  Bu qadam faqat ko'rish uchun.
- Tarifni yangilash yoki domenni uzaytirish tugmasini bosma.
- Har bir bo'limning ekranini ko'rib, nima yozilganini aynan ko'chirib
  ber — o'zingdan taxmin qilib to'ldirma.
- Biror ma'lumotni topa olmasang, "topilmadi" deb ayt. O'ylab topma.

Hisobotni tayyorlaganingdan keyin, muammoni tuzatish uchun nima
qilish kerakligini ayt — lekin ruxsatimsiz qilma.
```

---

## 2-prompt — tuzatish (birinchi hisobotdan KEYIN)

Faqat 1-prompt natijasi «DNS zonasi yo'q» yoki «A yozuvi yo'q» bo'lsa
yuboring.

```
Rahmat. Endi tuzatamiz. Quyidagini qadam-baqadam qil va har bir
o'zgarishdan OLDIN mendan tasdiq so'ra.

MAQSAD
`hamkorsavdo.uz` va `www.hamkorsavdo.uz` xosting serveriga ishora
qiladigan holga kelsin.

QADAMLAR
1. Agar domen xosting akkauntiga qo'shilmagan bo'lsa — uni qo'sh.
   ("Добавить домен" / "Add domain"). Turi: asosiy domen yoki parked
   emas, **oddiy sayt domeni** bo'lsin.
2. Domen uchun DNS zonasi yaratilsin.
3. Zonaga shu ikki yozuv qo'shilsin (IP — 1-hisobotda aniqlagan xosting
   serverining IP manzili):

   Turi: A     Nomi: @ (yoki bo'sh, yoki hamkorsavdo.uz)   Qiymati: <IP>
   Turi: A     Nomi: www                                    Qiymati: <IP>

   TTL ni tegmay qo'ysang ham bo'ladi (odatda 3600).
4. Saqlagandan keyin panel qanday xabar berganini menga ayt.

QOIDALAR
- Mavjud MX yoki TXT yozuvlarni O'CHIRMA. Agar pochta ishlab tursa,
  ularni o'chirish pochtani buzadi.
- NS serverlarni O'ZGARTIRMA. Ular hozir to'g'ri turibdi.
- Har bir "Сохранить" / "Save" tugmasidan oldin nima o'zgarayotganini
  menga ko'rsat va tasdiq so'ra.
- Parol, kod, to'lov — men kiritaman.

Tugagach ayt, men tashqaridan tekshiraman. DNS tarqalishi 15 daqiqadan
bir necha soatgacha vaqt oladi.
```

---

## 3-prompt — agar panelda hech narsa topilmasa

Ba'zan muammo xosting tarifining o'zida bo'ladi (muddati tugagan,
to'lanmagan). Unda panelda DNS bo'limi umuman ko'rinmaydi.

```
Panelda DNS bo'limi yo'q / domen umuman ko'rinmayapti. Unda ahost.uz
qo'llab-quvvatlash xizmatiga murojaat yozamiz.

Panelda "Поддержка" / "Тикеты" / "Support" bo'limini top va yangi
murojaat ochish oynasini menga ko'rsat. Mavzu va matnni quyidagicha
to'ldir, LEKIN yuborishdan oldin mendan tasdiq so'ra:

Mavzu:
hamkorsavdo.uz — DNS зона отсутствует, домен не резолвится

Matn:
Здравствуйте.

Домен hamkorsavdo.uz делегирован на ваши NS-серверы, но не резолвится
ни из одной точки мира.

При запросе к uz02.ahost.uz (37.153.159.12) сервер возвращает REFUSED
для этого домена. Cloudflare DNS сообщает:
"EDE(22): No Reachable Authority at delegation hamkorsavdo.uz"
"EDE(23): Network Error 37.153.159.12:53 returned REFUSED"

Похоже, DNS-зона для домена не создана на вашей стороне.

Просьба:
1. Проверить статус хостинга и домена по нашему аккаунту.
2. Создать DNS-зону для hamkorsavdo.uz.
3. Добавить A-записи для hamkorsavdo.uz и www.hamkorsavdo.uz,
   указывающие на IP нашего хостинга.

Заранее благодарю.
```

---

## Eslatmalar

- **Sayt manzili aynan `https://ahost.uz` bo'lsin.** Qidiruvdan kirmang —
  reklama havolalari orasida soxtasi chiqishi mumkin.
- Claude kengaytmasi har bir bosishdan oldin ruxsat so'raydi. Boshliq
  «Разрешить» bosmaguncha hech narsa o'zgarmaydi.
- 1-prompt xavfsiz: u faqat o'qiydi. Ikkinchisini yuborishdan oldin
  hisobotni menga tashlang — IP to'g'rimi, yozuv to'g'ri turibdimi,
  birga tekshiramiz.
- Tuzalganini tashqaridan shu buyruq bilan tekshirasiz:

  ```bash
  curl -sS -H "accept: application/dns-json" "https://cloudflare-dns.com/dns-query?name=hamkorsavdo.uz&type=A"
  ```

  `"Status":0` va `"Answer"` ichida IP chiqsa — ishladi.
