import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";

/** To'rtta filial-zali, "uch yo'nalish"ning barchasini ko'rsatadigan tartibda. */
const ABOUT_PHOTOS = [
  {
    src: "/filiallar/andijon-amir-temur/zal-4",
    alt: "HAMKOR SAVDO Andijon filiali — zargarlik peshtaxtasi va texnika bo'limi bir zalda",
  },
  {
    src: "/filiallar/shahrixon-ozodbek/zal-1",
    alt: "HAMKOR SAVDO Shahrixon (Ozodbek) filiali — tilla taqinchoqlar peshtaxtasi",
  },
  {
    src: "/filiallar/andijon-amir-temur/zal-2",
    alt: "HAMKOR SAVDO Andijon filiali — maishiy texnika qatorlari",
  },
  {
    src: "/filiallar/shahrixon-ozodbek/zal-4",
    alt: "HAMKOR SAVDO Shahrixon (Ozodbek) filiali — mebel bo'limi",
  },
];

/**
 * Scene 02 — "Ular nima qiladi?"
 *
 * Matn admin paneldan (`content.json > texts.aboutParagraphs`). Kompaniya
 * tarixi (`companyStory`) egasi yozmaguncha bo'sh va ko'rinmaydi.
 */
export function About() {
  return (
    <section
      id="biz-haqimizda"
      aria-labelledby="about-title"
      className="relative bg-ground py-16 sm:py-24"
    >
      <div className="mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20">
        <div data-reveal>
          <p className="kicker">Biz haqimizda</p>
          <h2 id="about-title" className="display mt-3 text-4xl sm:text-5xl">
            Bizga xaridor emas, <br className="hidden sm:block" />
            <span className="text-purple">HAMKOR</span> bo&apos;ling!
          </h2>

          {/* Sarlavha ostidagi bo'sh ustunni to'ldiradi va matndagi "tarmoq"
              so'zini ko'rsatib beradi. To'rtta haqiqiy surat, ikki filialdan,
              uch yo'nalishning barchasini ko'rsatib, navbat bilan almashinadi —
              hech qanday JavaScript yoki animatsiya kutubxonasiz, faqat CSS
              (marquee va media-slot bilan bir xil yondashuv). */}
          <div className="photo-cycle mt-8 aspect-[4/3] w-full overflow-hidden rounded-[28px]">
            {ABOUT_PHOTOS.map((p) => (
              <img
                key={p.src}
                src={`${p.src}-960.webp`}
                srcSet={`${p.src}-480.webp 480w, ${p.src}-960.webp 960w`}
                sizes="(max-width: 1024px) 100vw, 560px"
                width={960}
                height={720}
                alt={p.alt}
                className="object-cover"
                loading="lazy"
                decoding="async"
              />
            ))}
          </div>
        </div>

        <div data-reveal className="space-y-6 text-lg leading-relaxed text-ink-2">
          {content.texts.aboutParagraphs.map((p, i) => (
            <p key={i}>
              <Rich text={p} />
            </p>
          ))}

          {site.unpublished.companyStory ? (
            <p>
              <Rich text={site.unpublished.companyStory} />
            </p>
          ) : null}

          <div className="flex flex-wrap gap-3 pt-2">
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
