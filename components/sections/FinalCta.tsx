import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { Logo } from "@/components/ui/Logo";

/** Kollajdagi ikki haqiqiy surat — texnika zali va mebel bo'limi. */
const PHOTOS = [
  { src: "/filiallar/andijon-amir-temur/zal-2", alt: "HAMKOR SAVDO Andijon filiali — maishiy texnika zali" },
  { src: "/filiallar/shahrixon-ozodbek/zal-3", alt: "HAMKOR SAVDO Shahrixon filiali — yotoqxona mebeli" },
];

/**
 * Footer oldidagi yakuniy chaqiriq. Chuqur binafsha gradient; chapda sarlavha
 * va bitta sariq tugma (ariza formasiga, `#ariza`), o'ngda do'kondan ikki
 * surat kollaji va ikkita nishon. Matn admin panelda (`texts.finalCtaLead`).
 */
export function FinalCta() {
  const rating = site.reviews.rating.toFixed(1).replace(".", ",");
  return (
    <section id="hamkor-boling" aria-labelledby="cta-title" className="bg-ground py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div data-reveal className="cta-panel on-purple relative overflow-hidden rounded-[32px] text-white">
          <Logo
            variant="mark"
            className="pointer-events-none absolute -bottom-16 -left-12 h-72 w-auto text-white/[0.05]"
          />
          <div className="relative grid items-center gap-12 px-6 py-14 sm:px-12 sm:py-16 lg:grid-cols-[1.05fr_0.95fr] lg:gap-10 lg:px-16 lg:py-20">
            <div>
              <p className="kicker">Keyingi qadam</p>
              <h2 id="cta-title" className="display mt-4 text-4xl leading-[1.05] sm:text-5xl lg:text-6xl">
                Kerakli mahsulotni <span className="text-yellow">birga topamiz</span>
              </h2>
              <p className="mt-6 max-w-lg text-lg leading-relaxed text-on-purple-2">
                <Rich text={content.texts.finalCtaLead} strongClass="font-semibold text-white" />
              </p>
              <div className="mt-9 flex flex-wrap items-center gap-3">
                <a href="#ariza" className="btn btn-yellow btn-lift">
                  Ariza qoldirish
                  <span className="nudge" aria-hidden="true">&rarr;</span>
                </a>
                <a href={`tel:${site.phone}`} className="btn btn-outline">
                  {site.phoneDisplay}
                </a>
              </div>
              <ul className="mt-9 flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-purple-2">
                {["Pasport va plastik karta kifoya", "Kafilsiz", `${site.facts.branchCount} ta filial`].map((t) => (
                  <li key={t} className="flex items-center gap-2">
                    <span aria-hidden="true" className="h-1.5 w-1.5 rounded-full bg-yellow" />
                    {t}
                  </li>
                ))}
              </ul>
            </div>

            {/* Kollaj — faqat bezak emas: haqiqiy do'kon, "bu yerga kelaman" hissi. */}
            <div className="relative mx-auto h-72 w-full max-w-md sm:h-96 lg:h-[26rem]">
              <img
                src={`${PHOTOS[0].src}-960.webp`}
                srcSet={`${PHOTOS[0].src}-480.webp 480w, ${PHOTOS[0].src}-960.webp 960w`}
                sizes="(max-width: 1024px) 70vw, 360px"
                width={960}
                height={720}
                alt={PHOTOS[0].alt}
                loading="lazy"
                decoding="async"
                className="absolute right-0 top-0 h-[62%] w-[78%] rotate-[3deg] rounded-[24px] border-4 border-white/15 object-cover shadow-2xl"
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
                className="absolute bottom-0 left-0 h-[58%] w-[74%] -rotate-[4deg] rounded-[24px] border-4 border-white/15 object-cover shadow-2xl"
              />
              <p className="absolute left-2 top-6 rotate-[-3deg] rounded-[16px] bg-white px-4 py-3 text-ink shadow-xl sm:left-0">
                <span className="numeral block text-3xl leading-none text-purple">{site.facts.installmentMonthsMax} oy</span>
                <span className="mt-1 block text-xs font-semibold">muddatli to&apos;lov</span>
              </p>
              <p className="absolute bottom-10 right-0 rotate-[3deg] rounded-[16px] bg-yellow px-4 py-3 text-ink shadow-xl">
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
