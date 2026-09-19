# `hamkorsavdo.uz` ochilmayapti — sabab aniqlandi

**Tekshirilgan sana:** 2026-09-19
**Holati:** domen internetda mavjud emas. Sayt tayyor, lekin uni hech kim
ko'ra olmaydi.

---

## Boshliqqa yuboriladigan qisqa xat

> Assalomu alaykum.
>
> `hamkorsavdo.uz` domeni ro'yxatdan o'tgan va ahost.uz serveriga
> yo'naltirilgan, lekin **ahost.uz panelida bu domen uchun DNS zonasi
> yaratilmagan**. Shu sababli domen dunyoning hech bir joyidan ochilmaydi.
>
> Iltimos, ahost.uz shaxsiy kabinetiga kirib quyidagini tekshiring:
>
> 1. Xosting tarifi **faol**mi (muddati o'tib ketmaganmi)?
> 2. `hamkorsavdo.uz` domeni xosting akkauntiga **qo'shilganmi**?
>    («Домены» / «Domains» bo'limida ko'rinishi kerak)
> 3. O'sha domen uchun **DNS zonasi** bormi? Ichida kamida shu ikkitasi
>    bo'lishi shart:
>    - `hamkorsavdo.uz` → A yozuvi → serverning IP manzili
>    - `www.hamkorsavdo.uz` → A yozuvi (yoki CNAME) → o'sha IP
>
> Agar bu ish o'zingizga qiyin bo'lsa, ahost.uz qo'llab-quvvatlash
> xizmatiga shu xabarni yuborsangiz kifoya:
>
> > «`hamkorsavdo.uz` domeni sizning NS serveringizga yo'naltirilgan,
> > lekin `uz02.ahost.uz` (37.153.159.12) bu domen uchun **REFUSED**
> > qaytaryapti — zona yaratilmagan ko'rinadi. Iltimos, domen uchun DNS
> > zonasini oching va A yozuvini qo'ying.»
>
> Shu tuzalgach, sayt bir necha soat ichida ochiladi va qidiruv
> tizimlarida chiqa boshlaydi.

---

## Texnik dalil

Domen `.uz` reyestrida bor va ahost.uz nom serveriga topshirilgan. Lekin
o'sha server domen haqida so'ralganda javob berishdan bosh tortyapti:

```bash
curl -sS -H "accept: application/dns-json" \
  "https://cloudflare-dns.com/dns-query?name=hamkorsavdo.uz&type=NS"
```

```json
{
  "Status": 2,
  "Comment": [
    "EDE(22): No Reachable Authority at delegation hamkorsavdo.uz.",
    "EDE(23): Network Error 37.153.159.12:53 returned REFUSED for hamkorsavdo.uz NS"
  ]
}
```

`Status: 2` — SERVFAIL. `37.153.159.12` kimniki ekanini tekshirsak:

```bash
curl -sS -H "accept: application/dns-json" \
  "https://cloudflare-dns.com/dns-query?name=12.159.153.37.in-addr.arpa&type=PTR"
```

```json
{ "Answer": [ { "data": "uz02.ahost.uz." } ] }
```

Ya'ni ahost.uz ning o'z serveri. **REFUSED** javobi bitta narsani
bildiradi: server bu domen uchun mas'ul emasligini aytyapti — zona fayli
unda yo'q.

Buni tasdiqlash uchun ikkita mustaqil resolver ishlatildi (Google
`8.8.8.8` va Cloudflare `1.1.1.1`) — ikkalasi ham bir xil natija berdi,
ya'ni muammo mahalliy internetda emas.

## Nima muammo EMAS

Shu variantlar tekshirildi va chiqarib tashlandi:

- **Domen muddati tugagan emas** — agar tugagan bo'lsa, `.uz` reyestri
  delegatsiyani olib tashlagan bo'lardi. Delegatsiya joyida turibdi.
- **Mahalliy internet nosozligi emas** — ikki xil ommaviy resolver bir xil
  xato berdi.
- **Saytdagi xatolik emas** — kod, build va `.htaccess` sog'lom. Muammo
  fayllar yuklanadigan joyga yetib borishdan **oldin**.

## Shu tuzalgunicha

Internetda brendning yagona sayti — `hamkor-savdo.vercel.app`. U eski
versiya va unda noto'g'ri ma'lumot bor:

| Maydon | Eski vercel sayt | To'g'risi |
|---|---|---|
| Telefon | `+998 74 342 08 80` (o'chirilgan) | `+998 33 342 08 80` |
| Muddat | 18 oygacha | 12 oy |
| Tavsif (description) | yo'q | bor |
| `robots.txt` | yo'q (404) | bor |
| canonical | yo'q | bor |
| Metrika hisoblagichi | `90660926` (boshqa) | joriysi |

Bing va DuckDuckGo da indekslanmagan, ya'ni katta zarar hali yetmagan.
Ammo havolani bilgan odam o'chirilgan raqamga qo'ng'iroq qiladi.

Domen tuzalgach, bu saytni **o'chirish yoki `hamkorsavdo.uz` ga 301 bilan
yo'naltirish** kerak — aks holda ikkita bir xil sayt qidiruvda bir-birini
pastga tortadi.
