# Bosh sahifa rasmlari va videolari — manbalar (01.11.2026 redizayn)

| Fayl | Nima | Manba | Litsenziya |
|---|---|---|---|
| `public/uy/xona-640/1000/1400.webp` | Mehmonxona: televizor, komod, kreslo (bosh qismdagi "uy" oynasi) | Unsplash, Spacejoy — https://unsplash.com/photos/xyygOyZO5VA | Unsplash License (bepul, tijorat uchun ham; muallif ko'rsatish ixtiyoriy) |
| `public/uy/ozodbek-1280.mp4`, `ozodbek-poster-*.webp` | Shahrixon filiali (Ozodbek savdo markazi) tashqi ko'rinishi, 8 s | Kompaniyaning o'z videosi (Google Veo bilan jonlantirilgan, `hero-video` tarmog'i, 23.10) | HAMKOR SAVDO |
| `public/filiallar/**` | Filiallar ichi va tashqarisi | Kompaniyaning o'z suratlari (admin panel, `docs/rasmlar-tartibi.md`) | HAMKOR SAVDO |
| `public/yonalishlar/**` | Bo'limlar (texnika, mebel, tilla, skuter) | Filial suratlaridan kesilgan (`tools/bosh-rasmlar.py`) | HAMKOR SAVDO |
| `public/3d/*.webp` | Mahsulot tasvirlari (muzlatgich, divan, uzuk, skuter) | 3D modellardan render — mualliflar `lib/vitrina.ts` da, sahifada "3D modellar mualliflari" ostida | CC BY 4.0 / CC0 |
| `public/lavha/**` (build'da) | Mijoz lavhalari muqovasi | Rasmiy Telegram kanal @hamkorsavdouz (`tools/kanal-lavhalar.mjs`) | HAMKOR SAVDO |

Egasidan kerak bo'lsa: haqiqiy mijoz uyida mahsulot (yetkazib berilgan muzlatgich, o'rnatilgan konditsioner)
surati — `xona-*.webp` o'rniga shu qo'yiladi (nomi va o'lchami saqlansa, kod o'zgarmaydi).

- `public/yonalishlar/*-640.webp`, `public/3d/*-320.webp` (02.11) — mavjud -900 / 640 px fayllardan kichraytirilgan nusxalar (telefon uchun), manba o'sha.
