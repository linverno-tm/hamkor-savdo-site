"""
Bosh sahifa uchun maxsus kesilgan suratlarni tayyorlaydi.

`rasm-tayyorlash.py` filial galereyasi uchun 4:3 suratlar chiqaradi. Bu yerda
esa boshqa vazifa: sarlavha uchun keng kadr va yo'nalish kartochkalari uchun
uchta surat kerak. Ular boshqa nisbatda va boshqa joydan kesiladi, shuning
uchun alohida skript.

Nega qo'lda kesish kerak: avtomatik markazdan kesish muhim narsani kesib
tashlaydi. Masalan jamoa suratida binafsha logotipli devor yuqorida turibdi —
markazdan kessa, aynan logotip kadrdan chiqib ketadi.

Ishlatish:
    python tools/bosh-rasmlar.py
"""

import os

from PIL import Image, ImageOps

try:  # iPhone HEIC fayllari uchun
    import pillow_heif

    pillow_heif.register_heif_opener()
except ImportError:
    pass

ASL = r"D:/CLAUDE folder/HamkorSaVDO/rasmlar/asl"
ILDIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SIFAT = 78

# (manba, chiqish yo'li, nisbat, tik joylashuv 0..1, tepadan olib tashlash 0..1, o'lchamlar)
#
# "tik joylashuv" — kesilgan oyna qayerdan olinishi: 0 = eng tepadan,
#   0.5 = markazdan, 1 = eng pastdan. Faqat rasm kerakligidan tik bo'lsa ishlaydi.
# "tepadan olib tashlash" — nisbatga kesishdan OLDIN yuqoridan shuncha ulush
#   kesib tashlanadi. Manba allaqachon kerakli nisbatda bo'lsa, "tik joylashuv"
#   hech narsa qilmaydi (kesiladigan joy qolmaydi) — kadrni yuqoridan
#   qisqartirishning yagona yo'li shu.
ISHLAR = [
    # Sarlavha uchun: Andijon filialining jamoasi. Binafsha logotipli devor
    # butun to'plamdagi eng kuchli brend kadri, shuning uchun tepadan kesamiz.
    (
        "andijon-amir-temur/jamoa-1.JPG",
        "public/bosh/sarlavha",
        (4, 3),
        0.30,
        0.0,
        (640, 1024),
    ),
    # Yo'nalish kartochkalari.
    (
        "andijon-amir-temur/zal-2.JPG",
        "public/yonalishlar/texnika",
        (4, 3),
        0.50,
        0.0,
        (480, 900),
    ),
    (
        "andijon-amir-temur/zal-1.JPG",
        "public/yonalishlar/tilla",
        (4, 3),
        0.50,
        0.0,
        (480, 900),
    ),
    (
        "asaka-umid/zal-4.HEIC",
        "public/yonalishlar/mebel",
        (4, 3),
        0.5,
        0.30,  # yuqoridagi "18 oygacha" banneri kadrdan chiqib ketsin
        (480, 900),
    ),
]


def kes(im, nisbat, tik, tepa=0.0):
    """Rasmni berilgan nisbatga kesadi, oynani tik bo'ylab suradi."""
    if tepa > 0:
        W, H = im.size
        im = im.crop((0, int(H * tepa), W, H))

    W, H = im.size
    kerakli = nisbat[0] / nisbat[1]
    hozirgi = W / H

    if hozirgi > kerakli:
        # rasm juda keng — yon tomonlardan kesamiz, markazdan
        w = int(H * kerakli)
        x0 = (W - w) // 2
        return im.crop((x0, 0, x0 + w, H))

    # rasm juda tik — tepa/pastdan kesamiz, `tik` ko'rsatgan joydan
    h = int(W / kerakli)
    y0 = int((H - h) * tik)
    return im.crop((0, y0, W, y0 + h))


def main():
    for manba, chiqish, nisbat, tik, tepa, olchamlar in ISHLAR:
        yol = os.path.join(ASL, manba)
        if not os.path.isfile(yol):
            print("TOPILMADI:", yol)
            continue

        im = ImageOps.exif_transpose(Image.open(yol)).convert("RGB")
        kadr = kes(im, nisbat, tik, tepa)

        katalog = os.path.join(ILDIZ, os.path.dirname(chiqish))
        os.makedirs(katalog, exist_ok=True)

        for px in olchamlar:
            kichik = kadr.resize((px, round(px * nisbat[1] / nisbat[0])), Image.LANCZOS)
            # EXIF (jumladan GPS) butunlay tushib qolsin — yangi rasmga ko'chiramiz
            toza = Image.new("RGB", kichik.size)
            toza.paste(kichik)
            fayl = os.path.join(ILDIZ, "%s-%d.webp" % (chiqish, px))
            toza.save(fayl, "WEBP", quality=SIFAT, method=6)
            print("%-42s %4d KB" % (os.path.relpath(fayl, ILDIZ), os.path.getsize(fayl) // 1024))


if __name__ == "__main__":
    main()
