/**
 * Raqamni kirishda sanab chiqaradi (Reveal.tsx > countUp). Server matnni
 * aynan o'zgarmagan holda chizadi: "7000+", "12 oy", "4 ta" — JavaScript
 * bo'lmasa yoki kirillcha nusxada shu ko'rinish qoladi.
 *
 * Faqat `[data-reveal]` ichida ishlaydi. Raqam bilan boshlanmagan qiymat
 * oddiy matn bo'lib qoladi.
 */
export function CountUp({ value }: { value: string }) {
  const m = /^(\d+)(.*)$/.exec(value);
  if (!m) return <>{value}</>;
  return (
    <span data-count={m[1]} data-count-suffix={m[2]} data-count-plain="">
      {value}
    </span>
  );
}
