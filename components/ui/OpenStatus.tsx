/**
 * Filialning jonli holati: "Hozir ochiq · 18:00 gacha" / "Tez yopiladi" /
 * "Yopiq · ertaga 8:00 da ochiladi". Toshkent vaqti bo'yicha, har daqiqa
 * yangilanadi (lib/motion.ts > data-open-hours). Dam olish kuni yo'q
 * (egasi 22.10) — jadval har kuni bir xil.
 *
 * Server bo'sh joy chizadi: holat vaqtga bog'liq, statik HTML'ga yozib
 * bo'lmaydi. JavaScript'siz belgi ko'rinmaydi, ish vaqti esa yonida baribir
 * yozilgan. Matnni skript sahifa tiliga qarab tanlaydi (lotin, kirill, rus).
 */
export function OpenStatus({ hours, closed = false }: { hours: string; closed?: boolean }) {
  if (closed) return null;
  // Matn `data-text` atributida, ko'rinishi CSS ::after da. Skript sekin
  // hidratsiyadan oldin ulgurib qolsa, React qo'shimcha atributlardan
  // ogohlantiradi — ataylab, shuning uchun suppressHydrationWarning.
  return <span className="open-st" role="status" data-open-hours={hours} suppressHydrationWarning />;
}
