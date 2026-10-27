import { site } from "@/data/site";
import { branches } from "@/data/branches";
import { content } from "@/lib/content";
import { ScrubText } from "@/components/ui/ScrubText";
import { SplitWords } from "@/components/ui/SplitWords";

/**
 * Filiallarning tashqi ko'rinishi — "tarmoq" so'zini ko'rsatib beradi (egasi
 * 2026-09-22: bu yerda tovar emas, filiallar aylansin) — to'rtta filial, har biri bittadan.
 */
const ABOUT_PHOTOS = [
  { branch: "andijon-amir-temur", src: "/filiallar/andijon-amir-temur/tashqi-1" },
  { branch: "shahrixon-ozodbek", src: "/filiallar/shahrixon-ozodbek/tashqi-1" },
  { branch: "asaka-umid", src: "/filiallar/asaka-umid/tashqi-1" },
  { branch: "shahrixon-bog", src: "/filiallar/shahrixon-bog/tashqi-1" },
].map((p) => {
  const b = branches.find((x) => x.id === p.branch);
  const label = b ? `${b.city} — ${b.landmark}` : "";
  return { ...p, label, alt: `HAMKOR SAVDO filiali: ${label}` };
});

/**
 * "Ular nima qiladi?"
 *
 * Kompyuterda: chapda sarlavha va filial suratlari (o'zi almashinadi,
 * kirishda "darcha" bo'lib ochiladi) joyida turadi; o'ngda matn —
 * aylantirilgan sari so'zma-so'z yorishadi (ScrubText). Matn admin
 * paneldan (`content.json > texts.aboutParagraphs`). Kompaniya tarixi
 * (`companyStory`) egasi yozmaguncha bo'sh va ko'rinmaydi.
 */
export function About() {
  return (
    <section id="biz-haqimizda" aria-labelledby="about-title" className="sec about">
      <div className="wrap about-grid">
        <div className="about-side">
          <div className="about-sticky">
            <div data-m>
              <p className="eyebrow">Biz haqimizda</p>
              <h2 id="about-title" className="sh-title">
                <SplitWords text="Bizga xaridor emas, **HAMKOR** bo'ling!" />
              </h2>
            </div>
            {/* Suratlar o'zi almashadi (faqat harakatga ruxsat bo'lsa) — WCAG 2.2.2:
                5 s dan uzun harakatni to'xtatish imkoni bo'lsin. Tugma lib/motion.ts da. */}
            <div className="about-photo-wrap">
            <div data-m="wipe" className="photo-cycle about-photo">
              {ABOUT_PHOTOS.map((p) => (
                <figure key={p.src}>
                  <img
                    src={`${p.src}-960.webp`}
                    srcSet={`${p.src}-480.webp 480w, ${p.src}-960.webp 960w`}
                    sizes="(max-width: 1024px) 100vw, 560px"
                    width={960}
                    height={720}
                    alt={p.alt}
                    className="h-full w-full object-cover"
                    loading="lazy"
                    decoding="async"
                  />
                  <figcaption className="about-cap">{p.label}</figcaption>
                </figure>
              ))}
            </div>
            <button type="button" className="cycle-pause" data-cycle-pause aria-pressed="false" aria-label="Suratlar almashinuvini to'xtatish">
              <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                <path className="cp-pause" d="M6 4h3v12H6zM11 4h3v12h-3z" />
                <path className="cp-play" d="M6 4l10 6-10 6z" />
              </svg>
            </button>
            </div>
          </div>
        </div>

        <div className="about-text">
          {content.texts.aboutParagraphs.map((p, i) => (
            <p key={i} className="scrub">
              <ScrubText text={p} />
            </p>
          ))}
          {site.unpublished.companyStory ? (
            <p className="scrub">
              <ScrubText text={site.unpublished.companyStory} />
            </p>
          ) : null}
          <div data-m="up" className="flex flex-wrap gap-3 pt-4">
            <a href="#yonalishlar" className="btn btn-primary">
              Yo&apos;nalishlarni ko&apos;rish
            </a>
            <a href="#filiallar" className="btn btn-outline">
              Filiallar
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}
