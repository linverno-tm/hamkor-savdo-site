import { Fragment } from "react";

/**
 * Sarlavhani so'zlarga bo'ladi: har so'z o'z "darcha"sida pastdan
 * ko'tariladi (app/home.css > .sw). Matn bir xil qoladi — faqat <span>
 * o'ramlari qo'shiladi, ekran o'quvchisi va qidiruv tizimi uchun oddiy
 * matn. Kirillcha nusxa matn tugunlarini o'giradi, o'ramlar saqlanadi.
 *
 * `**so'z**` belgisi — urg'uli so'z (`accent` sinfi bilan).
 */
export function SplitWords({ text, accent = "sw-accent" }: { text: string; accent?: string }) {
  let n = 0;
  const parts = text.split(/\*\*(.+?)\*\*/g);
  return (
    <>
      {parts.map((part, pi) =>
        part
          .split(/(\s+)/)
          .filter((w) => w.length > 0)
          .map((w, wi) =>
            /^\s+$/.test(w) ? (
              <Fragment key={`${pi}-${wi}`}> </Fragment>
            ) : (
              <span key={`${pi}-${wi}`} className="sw">
                <span className={pi % 2 === 1 ? accent : undefined} style={{ ["--wi" as string]: n++ }}>
                  {w}
                </span>
              </span>
            ),
          ),
      )}
    </>
  );
}
