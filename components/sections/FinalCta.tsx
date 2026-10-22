import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { Logo } from "@/components/ui/Logo";
import { SplitWords } from "@/components/ui/SplitWords";

/** Kollajdagi ikki haqiqiy surat — texnika zali va mebel bo'limi. */
const PHOTOS = [
  { src: "/filiallar/andijon-amir-temur/zal-2", alt: "HAMKOR SAVDO Andijon filiali — maishiy texnika zali" },
  { src: "/filiallar/shahrixon-ozodbek/zal-3", alt: "HAMKOR SAVDO Shahrixon filiali — yotoqxona mebeli" },
];

/**
 * Footer oldidagi yakuniy chaqiriq — sariq "final" panel. Oldingi bo'lim
 * (ariza) binafsha, shuning uchun bu yerda brendning ikkinchi rangi.
 *
 * Panel aylantirilganda kattalashib ekranni egallaydi (app/home.css >
 * .finale), o'ngdagi suratlar va nishonlar sekin suzadi. Matn admin
 * panelda (`texts.finalCtaLead`).
 */
export function FinalCta() {
  const rating = site.reviews.rating.toFixed(1).replace(".", ",");
  return (
    <section id="hamkor-boling" aria-labelledby="cta-title" className="sec finale-sec">
      <div className="wrap">
        <div className="finale">
          <Logo variant="mark" className="finale-mark" />
          <div className="relative grid items-center gap-12 px-6 py-14 sm:px-12 sm:py-16 lg:grid-cols-[1.05fr_0.95fr] lg:gap-10 lg:px-16 lg:py-20">
            <div data-m>
              <p className="finale-eyebrow">Keyingi qadam</p>
              <h2 id="cta-title" className="finale-title">
                <SplitWords text="Kerakli mahsulotni **birga topamiz**" accent="finale-accent" />
              </h2>
              <p className="finale-lead">
                <Rich text={content.texts.finalCtaLead} strongClass="font-semibold text-ink" />
              </p>
              <div className="mt-9 flex flex-wrap items-center gap-3">
                <a href="#ariza" className="btn btn-primary btn-glow-p">
                  Ariza qoldirish
                  <span className="nudge" aria-hidden="true">&rarr;</span>
                </a>
                <a href={`tel:${site.phone}`} className="btn finale-ghost">
                  {site.phoneDisplay}
                </a>
              </div>
              <ul className="finale-list">
                {["Pasport va plastik karta kifoya", "Kafilsiz", `${site.facts.branchCount} ta filial`].map((t) => (
                  <li key={t}>
                    <span aria-hidden="true" className="finale-tick">
                      ✓
                    </span>
                    {t}
                  </li>
                ))}
              </ul>
            </div>

            {/* Kollaj — faqat bezak emas: haqiqiy do'kon, "bu yerga kelaman" hissi. */}
            <div data-m="zoom" className="finale-collage">
              <img
                src={`${PHOTOS[0].src}-960.webp`}
                srcSet={`${PHOTOS[0].src}-480.webp 480w, ${PHOTOS[0].src}-960.webp 960w`}
                sizes="(max-width: 1024px) 70vw, 360px"
                width={960}
                height={720}
                alt={PHOTOS[0].alt}
                loading="lazy"
                decoding="async"
                className="finale-photo finale-photo-a"
              />
              <img
                src={`${PHOTOS[1].src}-960.webp`}
                srcSet={`${PHOTOS[1].src}-480.webp 480w, ${PHOTOS[1].src}-960.webp 960w`}
                sizes="(max-width: 1024px) 70vw, 360px"
                width={960}
                height={720}
                alt={PHOTOS[1].alt}
                loading="lazy"
                decoding="async"
                className="finale-photo finale-photo-b"
              />
              <p className="finale-badge finale-badge-a">
                <span className="numeral block text-3xl leading-none text-purple">{site.facts.installmentMonthsMax} oy</span>
                <span className="mt-1 block text-xs font-semibold">muddatli to&apos;lov</span>
              </p>
              <p className="finale-badge finale-badge-b">
                <span className="numeral block text-3xl leading-none">{rating} ★</span>
                <span className="mt-1 block text-xs font-semibold">{site.reviews.source}da baho</span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
