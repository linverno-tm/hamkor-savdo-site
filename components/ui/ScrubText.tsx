import { Fragment } from "react";
import { fill } from "@/lib/content";

/**
 * Admin matni (`**qalin**` va `{oy}` kabi o'rinbosarlar bilan), lekin har
 * so'z alohida <span>da: sahifa aylantirilgan sari so'zlar xiradan to'liq
 * rangga o'tadi (app/home.css > .scrub, `animation-timeline: view()`).
 * Brauzer qo'llamasa yoki "kamroq harakat" bo'lsa — matn oddiy ko'rinadi.
 */
export function ScrubText({ text }: { text: string }) {
  const parts = fill(text).split(/\*\*(.+?)\*\*/g);
  return (
    <>
      {parts.map((part, pi) => {
        const words = part.split(/(\s+)/).filter((w) => w.length > 0);
        const nodes = words.map((w, wi) =>
          /^\s+$/.test(w) ? (
            <Fragment key={wi}> </Fragment>
          ) : (
            <span key={wi} className="scrub-w">
              {w}
            </span>
          ),
        );
        return pi % 2 === 1 ? (
          <strong key={pi} className="scrub-strong">
            {nodes}
          </strong>
        ) : (
          <Fragment key={pi}>{nodes}</Fragment>
        );
      })}
    </>
  );
}
