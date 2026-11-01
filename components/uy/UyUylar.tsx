import Link from "next/link";
import { site } from "@/data/site";
import type { Topic } from "@/data/topics";

/**
 * Yo'nalishlar ro'yxati "uylar" ko'rinishida — bosh sahifadagi UyYonalish
 * kartalari bilan bir xil shakl (tomli oyna + tom ustida mahsulot).
 * Yo'nalish sahifasining "Boshqa yo'nalishlar" va /ru/ bosh sahifasida.
 * Matn — data/topics.ts dagi kicker va h1.
 */
const PRODUCT: Partial<Record<Topic["id"], string>> = { texnika: "muzlatgich", mebel: "divan", tilla: "uzuk", skuter: "skuter" };

export function UyUylar({ lang, items }: { lang: "uz" | "ru"; items: Topic[] }) {
  const M = site.facts.installmentMonthsMax;
  return (
    <ul className="uylar" data-m="" data-m-stagger>
      {items.map((o) => {
        const to = `${lang === "ru" ? o.ruPath : o.path}/`;
        const product = PRODUCT[o.id];
        const inner = (
          <>
            <span className={`yon-uy${o.photo ? "" : " uylar-oy"}`}>
              {o.photo ? (
                <img
                  src={o.photo.src.replace("-900.", "-480.")}
                  srcSet={`${o.photo.src.replace("-900.", "-480.")} 480w, ${o.photo.src} 900w`}
                  sizes="(min-width: 1024px) 300px, 44vw"
                  width={480}
                  height={360}
                  alt=""
                  loading="lazy"
                  decoding="async"
                />
              ) : (
                <span aria-hidden="true">
                  <b>{M}</b>
                  {lang === "ru" ? "месяцев" : "oy"}
                </span>
              )}
            </span>
            {product ? (
              <img
                className="yon-pr"
                src={`/3d/${product}-320.webp`}
                srcSet={`/3d/${product}-320.webp 320w, /3d/${product}.webp 640w`}
                sizes="(min-width: 1024px) 140px, 21vw"
                width={640}
                height={640}
                alt=""
                loading="lazy"
                decoding="async"
              />
            ) : null}
            <span className="yon-txt">
              <span className="yon-name">{o[lang].kicker}</span>
              <span className="yon-line">{o[lang].h1}</span>
            </span>
          </>
        );
        return (
          <li key={o.id}>
            {/* Ruscha sahifada oddiy <a> — to'liq yuklanish (TopicPage > LangLink izohi). */}
            {lang === "ru" ? (
              <a href={to} className="yon-a">
                {inner}
              </a>
            ) : (
              <Link href={to} className="yon-a">
                {inner}
              </Link>
            )}
          </li>
        );
      })}
    </ul>
  );
}
