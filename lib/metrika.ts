/** Yandex Metrika hisoblagichi (egasining akkaunti, 2026-09-17). */
export const YM_ID = 112743600;

type Ym = (id: number, method: string, ...args: unknown[]) => void;

/** Skript faqat hamkorsavdo.uz da yuklanadi — localhost'da `ym` yo'q, jim o'tadi. */
export function ym(method: string, ...args: unknown[]) {
  const fn = (window as unknown as { ym?: Ym }).ym;
  fn?.(YM_ID, method, ...args);
}
