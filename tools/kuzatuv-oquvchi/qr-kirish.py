"""
Kuzatuv akkauntiga QR kod orqali kirish — kod kutmasdan.

Telegram kirish kodini ba'zan umuman yubormaydi yoki u topilmaydi. QR yo'lida
kod kerak emas: ekrandagi QR ni akkaunt ochiq turgan telefondagi Telegram bilan
skanerlaysiz. Kirilgach seans saqlanadi, keyin `python oquvchi.py` so'ramasdan
ishlaydi.

Telefonda: Telegram -> Sozlamalar -> Qurilmalar -> "Kompyuterni ulash"
(Settings -> Devices -> Link Desktop Device) -> QR ni skanerlang.
"""

import asyncio
import getpass
import os
import sys

BU_YER = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, BU_YER)

try:
    import qrcode
    import qrcode.image.svg
except ImportError:
    sys.exit("qrcode o'rnatilmagan. Buyruq: pip install qrcode")

from telethon import TelegramClient
from telethon.errors import SessionPasswordNeededError

from oquvchi import sozlamani_oqi

QR_FAYL = os.path.join(BU_YER, "kirish-qr.svg")


def chiz(url, birinchi):
    """QR ni terminalga va faylga chizish. Terminalda o'qilmasa — fayl ochiladi."""
    q = qrcode.QRCode(border=2)
    q.add_data(url)
    q.make(fit=True)
    print()
    q.print_ascii(invert=True)
    qrcode.make(url, image_factory=qrcode.image.svg.SvgPathImage).save(QR_FAYL)
    print(f"\nQR fayl: {QR_FAYL}")
    if birinchi:
        try:
            os.startfile(QR_FAYL)  # brauzerda ochiladi (faqat Windows)
        except (AttributeError, OSError):
            pass
    else:
        print("Yangi QR — fayl ochiq bo'lsa, brauzerda F5 bosing.")


async def asosiy():
    cfg = sozlamani_oqi()
    client = TelegramClient(os.path.join(BU_YER, "seans"), cfg["api_id"], cfg["api_hash"])
    await client.connect()
    try:
        if await client.is_user_authorized():
            men = await client.get_me()
            print(f"Allaqachon kirilgan: {men.first_name} @{men.username or ''}")
            print("Endi: python oquvchi.py")
            return

        print("Telefonda: Telegram -> Sozlamalar -> Qurilmalar -> 'Kompyuterni ulash'")
        print("(Settings -> Devices -> Link Desktop Device) va QR ni skanerlang.")
        print("QR har ~30 soniyada yangilanadi.")
        qr = await client.qr_login()
        birinchi = True
        while True:
            chiz(qr.url, birinchi)
            birinchi = False
            try:
                await qr.wait()
                break
            except asyncio.TimeoutError:
                await qr.recreate()
            except SessionPasswordNeededError:
                # Akkauntda ikki bosqichli parol bor — uni kompyuter oldidagi odam kiritadi.
                parol = getpass.getpass("Ikki bosqichli parol (yozilganda ko'rinmaydi): ")
                await client.sign_in(password=parol)
                break

        men = await client.get_me()
        print(f"\nKirildi: {men.first_name} @{men.username or ''}")
        print("Seans saqlandi. Endi: python oquvchi.py")
    finally:
        try:
            os.remove(QR_FAYL)  # kirish QR i endi kerak emas
        except OSError:
            pass
        await client.disconnect()


if __name__ == "__main__":
    try:
        asyncio.run(asosiy())
    except KeyboardInterrupt:
        print("\nTo'xtatildi.")
