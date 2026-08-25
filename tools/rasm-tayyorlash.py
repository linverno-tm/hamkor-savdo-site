"""
Filiallardan kelgan asl fotosuratlarni saytga tayyorlaydi.

Nima qiladi:
  - EXIF burilishini to'g'rilaydi (telefonda yonlab olingan rasm tik turadi);
  - EXIF ma'lumotini butunlay o'chiradi — telefon suratida GPS koordinatasi va
    qurilma ma'lumoti bo'ladi, ular saytga chiqmasligi kerak;
  - ikki o'lchamda WebP qilib saqlaydi (480px va 960px) — telefon kichigini,
    kompyuter kattasini yuklaydi;
  - 4:3 nisbatga qirqadi, chunki sayt shu nisbatda ko'rsatadi.

Ishlatish:
    python tools/rasm-tayyorlash.py

Asl rasmlarni shu yerga qo'ying:
    D:/CLAUDE folder/HamkorSaVDO/rasmlar/asl/<filial>/
Natija shu yerga tushadi:
    public/filiallar/<filial>/

Fayl nomi turiga qarab qo'yiladi: tashqi, zal, jamoa, mijoz.
Masalan: tashqi-1.jpg -> tashqi-1-480.webp, tashqi-1-960.webp
"""

import os
import re
import sys

from PIL import Image, ImageOps

try:  # iPhone HEIC fayllari uchun
    import pillow_heif
    pillow_heif.register_heif_opener()
except ImportError:
    print("Eslatma: pillow-heif o'rnatilmagan, HEIC fayllar o'tkazib yuboriladi.")
    print("O'rnatish uchun: python -m pip install pillow-heif")

ASL = r"D:/CLAUDE folder/HamkorSaVDO/rasmlar/asl"
CHIQISH = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "public", "filiallar")

OLCHAMLAR = (480, 960)
NISBAT = 4 / 3
SIFAT = 78
KIRISH_TURLARI = (".jpg", ".jpeg", ".png", ".webp", ".heic")
TURLAR = ("tashqi", "zal", "jamoa", "mijoz")

# Ayrim kadrlarni markazdan qirqish to'g'ri kelmaydi. Kalit: "<filial>/<nom>",
# qiymat: (chapdan, o'ngdan, tepadan, pastdan) — har biri 0..1 ulush, 4:3 ga
# qirqishdan OLDIN qo'llanadi.
#
# asaka-umid/jamoa-1: kadrning chap chekkasida kutib o'tirgan mijoz bor,
# yuqorisi esa bo'sh shift. Chapdan 15% olib tashlansa, yog'och devor va
# menejerlar stoli kadrni to'ldiradi.
#
# asaka-umid/zal-3 va zal-4: shu filialning devorida "18 oygacha — barcha
# tovarlar" banneri osilgan, sayt esa 24 oy deydi (buyurtmachi 2026-08-25 da
# tasdiqladi). Banner kadrning yuqori qismida, shuning uchun tepadan kesiladi.
# BU QATORLARNI O'CHIRMANG — o'chirilsa, skript keyingi safar bannerni qaytadan
# saytga chiqaradi va sayt o'z-o'ziga qarshi gapiradi.
QIRQIM = {
    "asaka-umid/jamoa-1": (0.15, 0.0, 0.00, 0.0),
    "asaka-umid/zal-3": (0.00, 0.0, 0.36, 0.0),
    "asaka-umid/zal-4": (0.00, 0.0, 0.30, 0.0),
}


def tayyorla(manba: str, filial: str) -> list[str]:
    nom = os.path.splitext(os.path.basename(manba))[0].lower()
    nom = re.sub(r"[^a-z0-9-]+", "-", nom).strip("-")
    if not nom.split("-")[0] in TURLAR:
        print(f"    ! '{os.path.basename(manba)}' nomi noto'g'ri — "
              f"nom {', '.join(TURLAR)} dan biri bilan boshlanishi kerak. Tashlab ketildi.")
        return []

    with Image.open(manba) as im:
        im = ImageOps.exif_transpose(im)  # burilishni to'g'rilash
        im = im.convert("RGB")            # EXIF va alfa yo'qoladi

        qirqim = QIRQIM.get(f"{filial}/{nom}")
        if qirqim:
            chap, ong, tepa, past = qirqim
            w, h = im.size
            im = im.crop((round(w * chap), round(h * tepa),
                          w - round(w * ong), h - round(h * past)))

        im = ImageOps.fit(im, _kadr(im.size), Image.LANCZOS)

        yozilgan = []
        papka = os.path.join(CHIQISH, filial)
        os.makedirs(papka, exist_ok=True)
        for kenglik in OLCHAMLAR:
            balandlik = round(kenglik / NISBAT)
            nusxa = im.resize((kenglik, balandlik), Image.LANCZOS)
            yol = os.path.join(papka, f"{nom}-{kenglik}.webp")
            nusxa.save(yol, "WEBP", quality=SIFAT, method=6)
            yozilgan.append(yol)
        return yozilgan


def _kadr(olcham: tuple[int, int]) -> tuple[int, int]:
    """Rasmni 4:3 ga qirqish uchun eng katta mos to'rtburchak."""
    w, h = olcham
    if w / h > NISBAT:
        return (round(h * NISBAT), h)
    return (w, round(w / NISBAT))


def main() -> int:
    if not os.path.isdir(ASL):
        print(f"Asl rasmlar papkasi topilmadi:\n  {ASL}")
        return 1

    jami, xato = 0, 0
    for filial in sorted(os.listdir(ASL)):
        papka = os.path.join(ASL, filial)
        if not os.path.isdir(papka):
            continue
        fayllar = [f for f in sorted(os.listdir(papka))
                   if f.lower().endswith(KIRISH_TURLARI)]
        print(f"\n{filial}: {len(fayllar)} ta rasm")
        if not fayllar:
            continue
        for f in fayllar:
            try:
                natija = tayyorla(os.path.join(papka, f), filial)
            except Exception as e:  # noqa: BLE001 — bitta buzuq fayl to'xtatmasin
                print(f"    ! {f}: {e}")
                xato += 1
                continue
            if natija:
                kb = sum(os.path.getsize(p) for p in natija) // 1024
                print(f"    {f}  ->  {len(natija)} ta fayl, {kb} KB")
                jami += 1

    print(f"\nTayyor: {jami} ta rasm ishlandi." + (f" {xato} tasida xato." if xato else ""))
    print("Endi `npm run build` qilib, out/ papkasini hostingga yuklang.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
