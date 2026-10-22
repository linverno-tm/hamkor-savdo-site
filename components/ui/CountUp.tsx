/**
 * Raqamni kirishda sanab chiqaradi (lib/motion.ts). Server matnni aynan
 * o'zgarmagan holda chizadi: "7000+", "12 oy", "4 ta" — JavaScript
 * bo'lmasa shu ko'rinish qoladi.
 *
 * Faqat raqam qismi sanaladi; qo'shimcha ("+", " oy") alohida matn bo'lib
 * turadi — kirillcha nusxada u "ой", "та" ga o'giriladi va skript unga
 * tegmaydi (atributdagi qo'shimcha o'girilmas edi, sanash oxirida lotincha
 * "oy" qaytib chiqardi).
 *
 * Faqat `[data-m]` ichida ishlaydi. Raqam bilan boshlanmagan qiymat oddiy
 * matn bo'lib qoladi.
 */
export function CountUp({ value }: { value: string }) {
  const m = /^(\d+)(.*)$/.exec(value);
  if (!m) return <>{value}</>;
  return (
    <>
      <span data-count={m[1]} data-count-plain="">
        {m[1]}
      </span>
      {m[2]}
    </>
  );
}
