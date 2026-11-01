import { site } from "@/data/site";

/**
 * Ichki sahifalar (yo'nalishlar, /ru/) uchun bosh qism — bosh sahifadagi
 * UyHero bilan bir xil til: tungi binafsha, tomli uy oynasida surat, ortida
 * sariq "soya", tom ustida mahsulot va "Pasport + karta" muhri.
 *
 * Matnlar sahifaning o'zidan keladi (data/topics.ts) — bu yerda yangi
 * matn yo'q, faqat joylashuv.
 */
export type UyTopHeroProps = {
  lang: "uz" | "ru";
  title: string;
  lead: string;
  applyLabel: string;
  crumbs?: React.ReactNode;
  photo: { src: string; srcSet?: string; sizes: string; alt: string; w: number; h: number; position?: string };
  /** Tom ustidan chiqib turadigan mahsulot (public/3d/<id>-320.webp). */
  product?: string;
};

export function UyTopHero({ lang, title, lead, applyLabel, crumbs, photo, product }: UyTopHeroProps) {
  const badge = lang === "ru" ? ["Паспорт", "+ карта"] : ["Pasport", "+ karta"];
  return (
    <section aria-labelledby="tp-title" className="uh uh--tp">
      <div className="u-wrap uh-grid">
        <div className="uh-head">
          {crumbs}
          <h1 id="tp-title" className="uh-title">
            {title}
          </h1>
        </div>

        <p className="uh-lead">{lead}</p>

        <div className="uh-cta">
          <a href="#ariza" className="btn btn-yellow">
            {applyLabel}
          </a>
          <a href={`tel:${site.phone}`} className="btn uh-call">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path
                d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinejoin="round"
              />
            </svg>
            {site.phoneDisplay}
          </a>
        </div>

        <figure className="uh-uy">
          <div className="uh-uy-frame">
            <img
              src={photo.src}
              srcSet={photo.srcSet}
              sizes={photo.sizes}
              width={photo.w}
              height={photo.h}
              alt={photo.alt}
              fetchPriority="high"
              style={photo.position ? { objectPosition: photo.position } : undefined}
            />
          </div>
          {product ? (
            <img
              className="uh-pr"
              src={`/3d/${product}-320.webp`}
              srcSet={`/3d/${product}-320.webp 320w, /3d/${product}.webp 640w`}
              sizes="(min-width: 1024px) 220px, 34vw"
              width={640}
              height={640}
              alt=""
              decoding="async"
            />
          ) : null}
          <span className="uh-badge" aria-hidden="true">
            <span>{badge[0]}</span>
            <span>{badge[1]}</span>
          </span>
        </figure>
      </div>
    </section>
  );
}
