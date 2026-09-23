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

Faqat do'konning o'z postlari olinadi (guruh adminlari yoki guruh nomidan yozilgan).
Guruhdagi mijozlarning savol-javoblari yuborilmaydi: ular tahlilga kerak emas, AI pulini
bekorga sarflaydi va begona odamlarning yozganini tashqariga chiqaradi.

Ishga tushirish: README.md ga qarang.
"""

import asyncio
import base64
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
    from telethon.tl.types import ChannelParticipantsAdmins, PeerChannel
except ImportError:
    sys.exit("Telethon o'rnatilmagan. Buyruq: pip install telethon")

BU_YER = os.path.dirname(os.path.abspath(__file__))
SOZLAMA = os.path.join(BU_YER, "sozlama.ini")
HOLAT = os.path.join(BU_YER, "holat.json")


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
        "oraliq": max(3, int(s.get("oraliq_daqiqa", "5"))),
        "eng_kop": int(s.get("bir_martada", "50")),
        "telefon": s.get("telefon", "").strip(),
    }


def holatni_oqi():
    """Har guruhda qaysi xabargacha ko'rilgani. Panel faqat YUBORILGAN
    xabarlarni biladi; yozuvsiz rasm yoki mijoz xabari yuborilmaydi, shuning
    uchun o'qilgan joyni shu yerda ham saqlaymiz — aks holda dastur o'sha
    xabarlarni har safar qaytadan o'qib, bir joyda aylanib qolardi."""
    try:
        with open(HOLAT, encoding="utf-8") as f:
            return json.load(f)
    except (OSError, ValueError):
        return {}


def holatni_yoz(h):
    tmp = HOLAT + ".tmp"
    with open(tmp, "w", encoding="utf-8") as f:
        json.dump(h, f)
    os.replace(tmp, HOLAT)


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


ADMINLAR = {}  # guruh nomi -> admin id lar to'plami (None — ro'yxat olinmadi)


async def adminlar(client, nom, manba):
    if nom in ADMINLAR:
        return ADMINLAR[nom]
    try:
        ids = set()
        async for u in client.iter_participants(manba, filter=ChannelParticipantsAdmins):
            ids.add(u.id)
        ADMINLAR[nom] = ids
    except Exception as e:
        # Ba'zi guruhlar a'zolarga adminlar ro'yxatini ko'rsatmaydi.
        # Unda hamma xabar yuboriladi, AI keraksizini o'zi "boshqa" deb ajratadi.
        print(f"  @{nom}: adminlar ro'yxati olinmadi ({e.__class__.__name__}) — hamma xabar olinadi")
        ADMINLAR[nom] = None
    return ADMINLAR[nom]


def dokonnikimi(m, admin_ids):
    """Xabarni do'kon yozganmi (admin yoki guruh nomidan)."""
    if admin_ids is None:
        return True
    if m.from_id is None or isinstance(m.from_id, PeerChannel):
        return True  # guruh/kanal nomidan (anonim admin yoki bog'langan kanal)
    return m.sender_id in admin_ids


RASM_CHEGARA = 1_200_000  # bayt; undan kattasi yuborilmaydi


async def rasm_ol(client, m):
    """Post rasmi (yoki videoning muqovasi) — base64, 1000 px gacha.

    Raqobatchilar narxni ko'pincha faqat rasmga yozadi ("12 OYGA 209 000
    so'mdan"), post matnida esa "changyutkich" xolos. Shuning uchun rasm ham
    saytga boradi, u yerda AI narxni o'qiydi va rasm darhol o'chiriladi.
    1000 px katta yozuvni o'qishga yetadi, to'liq o'lcham esa ortiqcha og'ir.
    """
    try:
        if getattr(m, "photo", None):
            olcham = None
            for s in getattr(m.photo, "sizes", []) or []:
                w = getattr(s, "w", 0) or 0
                if 0 < w <= 1000 and "Stripped" not in type(s).__name__:
                    olcham = s  # o'lchamlar kichikdan kattaga tartiblangan
            b = await client.download_media(m, file=bytes, thumb=olcham if olcham is not None else -1)
        elif getattr(m, "video", None):
            b = await client.download_media(m, file=bytes, thumb=-1)  # videoning muqovasi
        else:
            return ""
    except Exception as e:
        print(f"    rasm olinmadi ({e.__class__.__name__}) — matni bilan yuboriladi")
        return ""
    if not b or len(b) > RASM_CHEGARA:
        return ""
    return base64.b64encode(b).decode("ascii")


async def guruhni_oqi(client, guruh, holat, eng_kop, qayta=0):
    """Bitta guruhdan oxirgi ko'rilganidan keyingi xabarlar."""
    nom = guruh["username"]
    oxirgi = max(int(guruh.get("last_id") or 0), int(holat.get(nom, 0)))
    try:
        manba = await client.get_entity(nom)
    except Exception as e:
        print(f"  @{nom}: ochilmadi — {e}")
        return []
    admin_ids = await adminlar(client, nom, manba)
    chiqdi = []
    eng_katta = oxirgi
    try:
        if qayta:
            # --qayta: oxirgi N ta xabar qaytadan, rasmi bilan (o'qilgan joy o'zgarmaydi).
            xabarlar = client.iter_messages(manba, limit=qayta)
        elif oxirgi == 0:
            # Birinchi marta: faqat oxirgi xabarlar. Guruhning butun tarixini
            # (ba'zan yillar) o'qib o'tirmaymiz.
            xabarlar = client.iter_messages(manba, limit=eng_kop)
        else:
            # Keyingi safar: ESKIDAN YANGIGA, oxirgi ko'rilgandan boshlab.
            # Teskarisi (yangidan eskiga + limit) bo'lsa, kechasi limitdan ko'p
            # xabar yozilganda o'rtadagilari sakrab o'tib ketardi.
            xabarlar = client.iter_messages(manba, limit=eng_kop, min_id=oxirgi, reverse=True)
        async for m in xabarlar:
            eng_katta = max(eng_katta, m.id)
            matn = (m.message or "").strip()
            if not matn or not dokonnikimi(m, admin_ids):
                continue
            media = ""
            if getattr(m, "video", None):
                media = "video"
            elif getattr(m, "photo", None):
                media = "foto"
            post = {
                "source": nom,
                "post_id": m.id,
                "text": matn,
                "media": media,
                "posted_at": m.date.strftime("%Y-%m-%d %H:%M:%S"),
            }
            if media:
                rasm = await rasm_ol(client, m)
                if rasm:
                    post["rasm"] = rasm
            chiqdi.append(post)
    except FloodWaitError as e:
        print(f"  @{nom}: Telegram {e.seconds} soniya kutishni so'radi")
        await asyncio.sleep(min(e.seconds, 300))
    except Exception as e:
        print(f"  @{nom}: o'qilmadi — {e}")
    if not qayta:
        holat[nom] = eng_katta
    return chiqdi


def bolaklar(postlar, chegara=1_500_000, eng_kop=20):
    """Saytga bo'lib yuborish: bitta so'rov ~1.5 MB dan oshmasin (rasmlar bilan)."""
    bolak, hajm = [], 0
    for p in postlar:
        o = len(p.get("rasm", "")) + len(p["text"]) + 300
        if bolak and (hajm + o > chegara or len(bolak) >= eng_kop):
            yield bolak
            bolak, hajm = [], 0
        bolak.append(p)
        hajm += o
    if bolak:
        yield bolak


async def bir_aylanish(client, cfg, holat, qayta=0):
    try:
        javob = saytga(cfg, {"amal": "royxat"})
    except urllib.error.HTTPError as e:
        print(f"Panel javob bermadi: HTTP {e.code}" + (" — kalitni tekshiring" if e.code == 403 else ""))
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
        yangi = await guruhni_oqi(client, g, holat, cfg["eng_kop"], qayta)
        if yangi:
            rasmli = sum(1 for p in yangi if p.get("rasm"))
            print(f"  @{g['username']}: {len(yangi)} ta do'kon posti, {rasmli} tasi rasmi bilan")
        hammasi.extend(yangi)
    if hammasi:
        yozildi = otkazildi = 0
        for bolak in bolaklar(hammasi):
            try:
                natija = saytga(cfg, {"amal": "yuklash", "postlar": bolak})
            except Exception as e:
                # Holatni saqlamaymiz — keyingi safar shu xabarlar qayta yuboriladi.
                print(f"Yuborilmadi, keyingi safar qayta urinadi: {e}")
                return
            yozildi += natija.get("yozildi", 0)
            otkazildi += natija.get("otkazildi", 0)
        print(f"Panelga yuborildi: {yozildi} ta yangi, {otkazildi} ta o'tkazib yuborildi.")
    else:
        print("Yangi do'kon posti yo'q.")
    if not qayta:
        holatni_yoz(holat)


QULF = os.path.join(BU_YER, "ishlayapti.lock")


def qulf_ol():
    """Bir vaqtda ikkita nusxa ishlamasin: cron har 5 daqiqada chaqiradi,
    oldingisi hali tugamagan bo'lishi mumkin. 15 daqiqadan eski qulf —
    uzilib qolgan jarayonniki, olib tashlanadi."""
    try:
        if time.time() - os.path.getmtime(QULF) > 900:
            os.remove(QULF)
    except OSError:
        pass
    try:
        fd = os.open(QULF, os.O_CREAT | os.O_EXCL | os.O_WRONLY)
        os.write(fd, str(os.getpid()).encode())
        os.close(fd)
        return True
    except FileExistsError:
        return False


async def asosiy():
    cfg = sozlamani_oqi()
    bir_marta = "--bir-marta" in sys.argv
    qayta = 0
    if "--qayta" in sys.argv:
        # Oxirgi N ta xabarni rasmi bilan qayta yuborish (bir martalik). Masalan,
        # narxni rasmdan o'qish qo'shilganda oldin rasmsiz kelganlarini to'ldirish uchun.
        try:
            qayta = max(1, min(200, int(sys.argv[sys.argv.index("--qayta") + 1])))
        except (IndexError, ValueError):
            qayta = 50
        bir_marta = True
    client = TelegramClient(os.path.join(BU_YER, "seans"), cfg["api_id"], cfg["api_hash"])

    if bir_marta:
        # Cron yoki Windows avtoyuklashidan: hech narsa so'ramaydi (klaviatura
        # yo'q), bir marta o'qiydi va yopiladi.
        if not qulf_ol():
            print(time.strftime("[%Y-%m-%d %H:%M] ") + "oldingi nusxa hali ishlayapti — o'tkazildi")
            return
        try:
            await client.connect()
            if not await client.is_user_authorized():
                print("Seans yo'q yoki bekor qilingan. Avval: python qr-kirish.py")
                sys.exit(2)
            print(time.strftime("[%Y-%m-%d %H:%M] ") + "tekshirilyapti...")
            await bir_aylanish(client, cfg, holatni_oqi(), qayta)
        finally:
            await client.disconnect()
            try:
                os.remove(QULF)
            except OSError:
                pass
        return

    # Qo'lda ishga tushirilganda: kirilmagan bo'lsa kod so'raydi (kompyuter
    # oldidagi odam kiritadi), keyin har N daqiqada tekshirib turadi.
    if cfg["telefon"]:
        print("Kirish: " + cfg["telefon"] + " — kod Telegram ilovasiga keladi.")
        await client.start(phone=cfg["telefon"])
    else:
        await client.start()
    men = await client.get_me()
    nom = "@" + men.username if men.username else "(username yo'q)"
    print(f"Telegram akkaunt: {men.first_name} {nom}")
    print(f"Har {cfg['oraliq']} daqiqada tekshiradi. To'xtatish: Ctrl+C\n")
    holat = holatni_oqi()
    while True:
        print(time.strftime("[%H:%M] ") + "tekshirilyapti...")
        await bir_aylanish(client, cfg, holat)
        await asyncio.sleep(cfg["oraliq"] * 60)


def ishga_tushir(coro):
    # asyncio.run Python 3.7 dan bor; hostingdagi eskiroq Python'da ham ishlasin.
    if hasattr(asyncio, "run"):
        return asyncio.run(coro)
    return asyncio.get_event_loop().run_until_complete(coro)


if __name__ == "__main__":
    try:
        ishga_tushir(asosiy())
    except KeyboardInterrupt:
        print("\nTo'xtatildi.")
