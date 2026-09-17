import { site } from "@/data/site";

/**
 * Scene 01 — "Bu kim va menga nima beradi?"
 *
 * A first-time visitor gets the brand statement, the headline offer, a way to
 * act, and — the part that used to be missing — a look at the actual shop. A
 * retailer whose first screen carries no photograph asks the visitor to take
 * the whole thing on trust; the Andijon floor shot answers "what is this
 * place?" before a word is read.
 *
 * The photo is the LCP element, so it loads eagerly at high priority while the
 * rest of the page stays lazy. Two widths are written at build time by
 * `tools/bosh-rasmlar.py` and picked through srcset — a phone on a slow
 * connection pulls 41 KB, not 96 KB.
 *
 * The entrance animation is a CSS `rise` keyframe with a per-item delay — it
 * costs nothing and never gates the content.
 */
export function Hero() {
  const facts = [
    { value: site.facts.productCount, label: "mahsulot" },
    { value: `${site.facts.installmentMonthsMax} oy`, label: "muddatli to'lov" },
    { value: String(site.facts.branchCount), label: "filial" },
    {
      value: "0 so'm",
      label: "yetkazish va o'rnatish",
      // Bepul yetkazish/o'rnatish faqat Andijon viloyatida — pastdagi
      // izohdagi shart shu yerda ham qisqacha eslatiladi.
      note: `(${site.freeDeliveryArea}da)`,
    },
  ];

  return (
    <section id="hero" aria-labelledby="hero-title" className="relative overflow-hidden pt-28 sm:pt-32">
      <div className="relative mx-auto max-w-7xl px-5 pb-20 sm:px-8 lg:pb-28">
        <div className="grid items-center gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
          <div>
            <p
              className="rise inline-flex items-center gap-2 rounded-full border border-line bg-ground px-4 py-2 text-sm font-medium text-ink-2"
              style={{ ["--i" as string]: 0 }}
            >
              <span className="h-2 w-2 rounded-full bg-yellow" aria-hidden="true" />
              {site.deliveryArea} yetkazamiz · {site.facts.branchCount} ta filial
            </p>

            <h1
              id="hero-title"
              className="rise display mt-6 text-[2.75rem] leading-[1.02] sm:text-6xl lg:text-7xl"
              style={{ ["--i" as string]: 1 }}
            >
              Oilangizga <br className="hidden sm:block" />
              ishonchli{" "}
              <span className="text-purple">hamkor</span>
            </h1>

            <p
              className="rise mt-6 max-w-xl text-lg leading-relaxed text-ink-2 sm:text-xl"
              style={{ ["--i" as string]: 2 }}
            >
              Tilla, texnika va mebel — {site.facts.productCount} mahsulot bitta do'konda.{" "}
              <strong className="font-semibold text-ink">
                {site.facts.installmentMonthsMax} oygacha muddatli to'lov
              </strong>
              , rasmiylashtirish uchun pasport va plastik kifoya.
            </p>

            <div className="rise mt-9 flex flex-col gap-3" style={{ ["--i" as string]: 3 }}>
              <div className="flex flex-wrap gap-3">
                <a href="#filiallar" className="btn btn-primary">
                  Eng yaqin filialni toping
                </a>
                <a href="#ariza" className="btn btn-outline">
                  Ariza qoldirish
                </a>
              </div>

              <a
                href="#maxsus-buyurtma"
                className="tap flex max-w-md items-center gap-3 rounded-2xl border-2 border-yellow bg-yellow/10 px-4 py-3 transition-colors hover:bg-yellow/20"
              >
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-yellow text-lg" aria-hidden="true">
                  ✓
                </span>
                <span className="text-sm font-semibold leading-snug text-ink sm:text-base">
                  Bizda yo&apos;qmi? Baribir <span className="text-purple">muddatli to&apos;lovga</span> olib beramiz →
                </span>
              </a>
            </div>
          </div>

          {/* The shop, with the quick-answer panel overlapping its lower edge.
              The overlap is a negative margin, never a transform: a transformed
              element becomes the containing block for its `position: fixed`
              descendants, which is what once trapped the photo lightbox inside
              <main> instead of the viewport. */}
          <div className="rise relative" style={{ ["--i" as string]: 3 }}>
            <img
              src="/bosh/sarlavha-1024.webp"
              srcSet="/bosh/sarlavha-640.webp 640w, /bosh/sarlavha-1024.webp 1024w"
              sizes="(max-width: 1024px) 100vw, 700px"
              width={1024}
              height={768}
              alt="HAMKOR SAVDO do'koni ichkarisi — jamoamiz mijozlarni kutib olmoqda"
              className="block aspect-[4/2.7] w-full rounded-[28px] object-cover object-bottom"
              loading="eager"
              fetchPriority="high"
              decoding="async"
            />

            <div className="on-purple relative z-10 -mt-12 rounded-[28px] bg-purple p-7 text-white sm:-mt-16 sm:ml-10 sm:p-9">
              <p className="kicker">Qisqacha</p>
              <dl className="mt-6 grid grid-cols-2 gap-x-6 gap-y-7">
                {facts.map((f) => (
                  /* flex-col-reverse keeps <dt> before <dd> in the DOM (as the spec
                     requires) while showing the number above its label. */
                  <div key={f.label} className="flex flex-col-reverse gap-2">
                    <dt className="text-sm leading-snug text-on-purple-2">
                      {f.label}
                      {f.note ? <span className="mt-0.5 block text-xs text-on-purple-2/70">{f.note}</span> : null}
                    </dt>
                    <dd className="numeral text-4xl text-yellow sm:text-5xl">{f.value}</dd>
                  </div>
                ))}
              </dl>
              <p className="mt-8 border-t border-white/20 pt-5 text-sm leading-relaxed text-on-purple-2">
                {`${site.freeDeliveryArea}da yetkazish va o'rnatish bepul. ${site.deliveryArea} yetkazib beramiz.`}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Brand marquee — the guidebook's bold, confident voice, as one CSS keyframe. */}
      <div className="marquee overflow-hidden border-y border-line bg-ground-2 py-4" aria-hidden="true">
        <div className="marquee-track">
          {Array.from({ length: 2 }).map((_, dup) => (
            <div key={dup} className="flex shrink-0 items-center">
              {[
                "TILLA",
                "TEXNIKA",
                "SKUTERLAR",
                "MEBEL",
                "MUDDATLI TO'LOV",
                "BEPUL YETKAZISH (ANDIJON)",
                "BEPUL O'RNATISH",
                "4 TA FILIAL",
              ].map((word) => (
                <span key={word} className="flex shrink-0 items-center">
                  <span className="numeral px-6 text-2xl tracking-[0.12em] text-ink-3">{word}</span>
                  <span className="h-2 w-2 shrink-0 rounded-full bg-yellow" />
                </span>
              ))}
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
