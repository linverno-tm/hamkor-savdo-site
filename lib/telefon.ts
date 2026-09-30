/**
 * Telefon raqamini yozilayotganda o'qiladigan ko'rinishga keltirish:
 * `+998 99 107 55 08`. Bir qatorga tizilgan 12 ta raqamni odam ko'z bilan
 * tekshira olmaydi — xato terilgan bitta raqam arizani yaroqsiz qiladi.
 *
 * Serverga baribir raqamlarning o'zi boradi (`api/lead.php` harf-belgilarni
 * tashlab, `+998...` ko'rinishiga keltiradi), shuning uchun bu yerdagi
 * bo'shliqlar faqat ko'rish uchun.
 */

/** Ichki: 9 xonali milliy raqamni bo'laklarga ajratadi. */
function bolaklar(milliy: string): string {
  const b = [milliy.slice(0, 2), milliy.slice(2, 5), milliy.slice(5, 7), milliy.slice(7, 9)];
  return b.filter(Boolean).join(" ");
}

/**
 * `991075508`, `+998991075508`, `998 99 107 55 08` → `+998 99 107 55 08`.
 *
 * Xorijiy raqam (`+7...`, `+90...`) tegilmaydi: do'konga boshqa davlatdan
 * ham yozishlari mumkin va ularning raqamini 998 ga majburlash xato bo'lardi.
 */
export function telefonFormat(xom: string): string {
  const matn = xom.trim();
  const raqamlar = matn.replace(/\D/g, "");

  if (raqamlar === "") {
    return matn.startsWith("+") ? "+" : "";
  }
  // "+" bilan boshlangan va 998 emas — xorijiy raqam, tegilmaydi.
  if (matn.startsWith("+") && !raqamlar.startsWith("998")) {
    return matn;
  }
  if (raqamlar.startsWith("998")) {
    const milliy = raqamlar.slice(3, 12);
    return milliy === "" ? "+998" : "+998 " + bolaklar(milliy);
  }
  /* Prefiks faqat to'qqizta raqam terilgandan keyin qo'yiladi.
     Ilgari u birinchi raqamdanoq qo'shilardi va odam "998..." deb qo'lda
     tersa, o'zi qo'shgan prefiks bilan qo'shilib ketardi: "998991075508"
     -> "+998 99 899 10 75". Endi terilgani o'z holicha ko'rinadi, prefiks
     esa raqam to'lganda paydo bo'ladi. */
  const milliy = raqamlar.slice(0, 9);
  return (milliy.length === 9 ? "+998 " : "") + bolaklar(milliy);
}

/**
 * `onInput` uchun ishlov: maydonni joyida formatlaydi.
 *
 * Faqat kursor matn OXIRIDA turganda qayta yozadi. O'rtasini tahrirlayotgan
 * odamda qayta formatlash kursorni oxiriga uloqtirib yuborardi.
 */
export function telefonInput(e: { currentTarget: HTMLInputElement }): void {
  const el = e.currentTarget;
  const oxiridami = el.selectionStart === el.value.length;
  const yangi = telefonFormat(el.value);
  if (yangi === el.value) {
    return;
  }
  el.value = yangi;
  if (oxiridami) {
    el.setSelectionRange(yangi.length, yangi.length);
  }
}
