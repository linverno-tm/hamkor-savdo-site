"""
HAMKOR SAVDO — raqobatchilar guruhlarini o'qiydigan dastur.

Nega kerak: Telegram boti faqat O'ZI a'zo bo'lgan chatni ko'ra oladi. Raqobatchilarning
Shahrixon va Andijondagi do'kon guruhlari — aynan model va oylik to'lov yozilgan joy —
guruh bo'lgani uchun na bot, na ochiq t.me/s/ sahifasi orqali o'qiladi. Shuning uchun bu
dastur oddiy Telegram AKKAUNTI bilan ishlaydi: ochiq guruhga xuddi odam kabi a'zo bo'ladi
va yangi xabarlarni o'qiydi.

Dastur FAQAT O'QIYDI. Hech qayerga xabar yozmaydi, hech kimni qo'shmaydi, hech narsani
o'chirmaydi. Yangi xabarlarni saytdagi panelga yuboradi, qolgan ishni (tahlil, xulosa,
ogohlantirish) panel bajaradi.

Ishga tushirish: README.md ga qarang.
"""

import configparser
import json
import os
import sys
import time
import urllib.error
import urllib.request

try:
    from telethon import TelegramClient
    from telethon.errors import FloodWaitError
except ImportError:
    sys.exit("Telethon o'rnatilmagan. Buyruq: pip install telethon")

BU_YER = os.path.dirname(os.path.abspath(__file__))
SOZLAMA = os.path.join(BU_YER, "sozlama.ini")


def sozlamani_oqi():
    if not os.path.isfile(SOZLAMA):
        sys.exit(
            "sozlama.ini topilmadi.\n"
            "sozlama.ini.namuna faylidan nusxa oling va nomini sozlama.ini qiling."
        )
    c = configparser.ConfigParser()
    c.read(SOZLAMA, encoding="utf-8")
    s = c["kuzatuv"]
    kerak = ("api_id", "api_hash", "sayt", "kalit")
    yoq = [k for k in kerak if not s.get(k, "").strip()]
    if yoq:
        sys.exit("sozlama.ini da to'ldirilmagan: " + ", ".join(yoq))
    return {
        "api_id": int(s["api_id"]),
        "api_hash": s["api_hash"].strip(),
        "sayt": s["sayt"].strip().rstrip("/"),
        "kalit": s["kalit"].strip(),
        "oraliq": int(s.get("oraliq_daqiqa", "5")),
        "eng_kop": int(s.get("bir_martada", "50")),
    }


def saytga(cfg, payload):
    """Panelga so'rov. Kalit sarlavhada ketadi — manzil qatorida emas."""
    so = urllib.request.Request(
        cfg["sayt"] + "/api/kuzatuv.php",
        data=json.dumps(payload, ensure_ascii=False).encode("utf-8"),
        headers={
            "Content-Type": "application/json; charset=utf-8",
            "X-HS-Key": cfg["kalit"],
            "User-Agent": "HamkorSavdo-kuzatuv-oquvchi/1.0",
        },
        method="POST",
    )
    with urllib.request.urlopen(so, timeout=30) as j:
        return json.loads(j.read().decode("utf-8"))


async def guruhni_oqi(client, guruh, eng_kop):
    """Bitta guruhdan oxirgi ko'rilganidan keyingi xabarlar."""
    nom = guruh["username"]
    oxirgi = int(guruh.get("last_id") or 0)
    try:
        manba = await client.get_entity(nom)
    except Exception as e:
        print(f"  @{nom}: ochilmadi — {e}")
        return []
    chiqdi = []
    try:
        # min_id — shu raqamdan KEYINGI xabarlar. Birinchi marta (oxirgi = 0)
        # oxirgi 50 tasini olamiz, keyin faqat yangilari keladi.
        async for m in client.iter_messages(manba, limit=eng_kop, min_id=oxirgi):
            matn = (m.message or "").strip()
            if not matn:
                continue  # rasm ostida yozuv bo'lmasa, tahlil qiladigan narsa yo'q
            media = ""
            if getattr(m, "video", None):
                media = "video"
            elif getattr(m, "photo", None):
                media = "foto"
            chiqdi.append({
                "source": nom,
                "post_id": m.id,
                "text": matn,
                "media": media,
                "posted_at": m.date.strftime("%Y-%m-%d %H:%M:%S"),
            })
    except FloodWaitError as e:
        print(f"  @{nom}: Telegram {e.seconds} soniya kutishni so'radi")
        time.sleep(min(e.seconds, 300))
    except Exception as e:
        print(f"  @{nom}: o'qilmadi — {e}")
    return chiqdi


async def bir_aylanish(client, cfg):
    try:
        javob = saytga(cfg, {"amal": "royxat"})
    except urllib.error.HTTPError as e:
        print(f"Panel javob bermadi: HTTP {e.code} — kalitni tekshiring")
        return
    except Exception as e:
        print(f"Panelga ulanilmadi: {e}")
        return
    guruhlar = javob.get("guruhlar") or []
    if not guruhlar:
        print("Panelda guruh ko'rsatilmagan. Raqobatchilar bo'limida guruh qo'shing.")
        return
    hammasi = []
    for g in guruhlar:
        yangi = await guruhni_oqi(client, g, cfg["eng_kop"])
        if yangi:
            print(f"  @{g['username']}: {len(yangi)} ta yangi xabar")
        hammasi.extend(yangi)
    if not hammasi:
        print("Yangi xabar yo'q.")
        return
    try:
        natija = saytga(cfg, {"amal": "yuklash", "postlar": hammasi})
        print(f"Panelga yuborildi: {natija.get('yozildi', 0)} ta yozildi, "
              f"{natija.get('otkazildi', 0)} ta o'tkazib yuborildi.")
    except Exception as e:
        print(f"Yuborilmadi: {e}")


async def asosiy():
    cfg = sozlamani_oqi()
    seans = os.path.join(BU_YER, "seans")
    client = TelegramClient(seans, cfg["api_id"], cfg["api_hash"])
    # Birinchi ishga tushirishda Telegram telefon raqami va SMS kodini so'raydi —
    # ularni KOMPYUTER OLDIDAGI ODAM kiritadi. Keyin seans faylga saqlanadi va
    # boshqa so'ralmaydi.
    await client.start()
    men = await client.get_me()
    print(f"Telegram akkaunt: {men.first_name} (@{men.username or 'username yo’q'})")
    print(f"Har {cfg['oraliq']} daqiqada tekshiradi. To'xtatish: Ctrl+C\n")
    while True:
        print(time.strftime("[%H:%M] ") + "tekshirilyapti…")
        await bir_aylanish(client, cfg)
        await client.loop.run_in_executor(None, time.sleep, cfg["oraliq"] * 60)


if __name__ == "__main__":
    try:
        import asyncio
        asyncio.run(asosiy())
    except KeyboardInterrupt:
        print("\nTo'xtatildi.")
