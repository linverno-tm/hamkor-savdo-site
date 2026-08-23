# Filiallardan kelgan rasmlarni saqlash tartibi

Ikki xil nusxa, ikki xil joyda. Ularni aralashtirmaslik muhim.

## 1. Asl nusxalar — arxiv (saytga tushmaydi)

```
D:\CLAUDE folder\HamkorSaVDO\rasmlar\asl\
    shahrixon-ozodbek\
    shahrixon-bog\
    asaka-umid\
    andijon-amir-temur\
```

Telegramdan kelgan fayllarni **o'zgartirmasdan** shu papkalarga yuklab qo'ying.

Nega kerak: sayt keyinchalik qayta ishlansa yoki boshqa o'lcham kerak bo'lsa,
asl nusxa bo'lmasa hammasini qaytadan suratga olish kerak bo'ladi. Telegram —
arxiv emas: chat tozalanishi, telefon almashishi mumkin. Bu papkani vaqti-vaqti
bilan tashqi diskka yoki bulutga nusxalab turing.

**Muhim:** Telegramda rasm "Rasm" ko'rinishida yuborilsa, u siqiladi va sifati
tiklanmaydi. Shuning uchun xatda "Fayl" qilib yuborish so'ralgan.

## 2. Saytdagi nusxalar — tayyorlangan

```
portfolio/hamkor-savdo-flagship/public/filiallar/<filial>/
```

Bu yerga qo'lda hech narsa qo'ymaysiz — quyidagi buyruq o'zi tayyorlaydi:

```bash
python tools/rasm-tayyorlash.py
```

## Fayl nomlari

Asl fayl nomi turi bilan boshlanishi shart, aks holda skript uni tashlab ketadi:

| Nom | Nimasi |
|---|---|
| `tashqi-1.jpg` | do'kon old qismi, viveska |
| `zal-1.jpg`, `zal-2.jpg` | savdo zali, bo'limlar |
| `jamoa-1.jpg` | shartnoma menejerlari, xodimlar |
| `mijoz-1.jpg` | mijoz bilan ishlash lahzasi |

## Skript nima qiladi

- Telefonda yonlab olingan rasmni **to'g'ri buradi**;
- **EXIF ma'lumotini butunlay o'chiradi** — telefon suratida GPS koordinatasi va
  qurilma ma'lumoti bo'ladi, ular saytga chiqmasligi kerak;
- 4:3 nisbatga qirqadi va **ikki o'lchamda WebP** qiladi (480px va 960px):
  telefon kichigini, kompyuter kattasini yuklaydi.

Nega bu shart: telefondan kelgan rasm odatda 3–5 MB. To'rt filial × 15 rasm =
250 MB. Bunday sayt sekin internetda umuman ochilmaydi. Tayyorlangandan keyin
har bir rasm ~50–120 KB bo'ladi — ya'ni **40 barobar yengil**, ko'zga esa farqi
bilinmaydi.

## Tartib

1. Telegramdan fayllarni `rasmlar\asl\<filial>\` ga yuklang
2. Nomlarini yuqoridagi jadval bo'yicha o'zgartiring
3. `python tools/rasm-tayyorlash.py`
4. `npm run build`
5. `out/` papkasini hostingga yuklang
