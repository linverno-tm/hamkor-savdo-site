import { site } from "@/data/site";

/**
 * Scene 01 — "Bu kim va menga nima beradi?"
 *
 * A first-time visitor gets the brand statement, the three directions, the
 * headline offer and a way to act, all above the fold and all in plain text.
 * The entrance animation is a CSS `rise` keyframe with a per-item delay — it
 * costs nothing and never gates the content.
 */
export function Hero() {
  const facts = [
    { value: site.facts.productCount, label: "mahsulot" },
    { value: `${site.facts.installmentMonthsMax} oy`, label: "muddatli to'lov" },
    { value: String(site.facts.branchCount), label: "filial" },
    { value: "0 so'm", label: "yetkazish va o'rnatish" },
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
              {site.serviceArea} · {site.facts.branchCount} ta filial
            </p>

            <h1
              id="hero-title"
              className="rise display mt-6 text-[2.75rem] leading-[1.02] sm:text-6xl lg:text-7xl"
              style={{ ["--i" as string]: 1 }}
            >
              Oilangizga <br className="hidden sm:block" />
              ishonchli{" "}
              <span className="relative z-0 text-purple">
                <span className="mark">hamkor</span>
              </span>
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

            <div className="rise mt-9 flex flex-wrap gap-3" style={{ ["--i" as string]: 3 }}>
              <a href="#filiallar" className="btn btn-primary">
                Eng yaqin filialni toping
              </a>
              <a href={`tel:${site.phone}`} className="btn btn-outline">
                {site.phoneDisplay}
              </a>
            </div>

            <ul
              className="rise mt-9 flex flex-wrap gap-2"
              style={{ ["--i" as string]: 4 }}
              aria-label="Yo'nalishlar"
            >
              {["TILLA", "TEXNIKA", "MEBEL"].map((c) => (
                <li
                  key={c}
                  className="numeral rounded-full bg-purple-50 px-4 py-2 text-lg tracking-[0.14em] text-purple"
                >
                  {c}
                </li>
              ))}
            </ul>
          </div>

          {/* Quick-answer panel: the four numbers that decide whether someone visits. */}
          <div
            className="on-purple rise rounded-[28px] bg-purple p-7 text-white sm:p-9"
            style={{ ["--i" as string]: 3 }}
          >
            <p className="kicker">Qisqacha</p>
            <dl className="mt-6 grid grid-cols-2 gap-x-6 gap-y-7">
              {facts.map((f) => (
                /* flex-col-reverse keeps <dt> before <dd> in the DOM (as the spec
                   requires) while showing the number above its label. */
                <div key={f.label} className="flex flex-col-reverse gap-2">
                  <dt className="text-sm leading-snug text-on-purple-2">{f.label}</dt>
                  <dd className="numeral text-4xl text-yellow sm:text-5xl">{f.value}</dd>
                </div>
              ))}
            </dl>
            <p className="mt-8 border-t border-white/20 pt-5 text-sm leading-relaxed text-on-purple-2">
              Bepul yetkazib berish va o&apos;rnatish — {site.serviceArea}.
            </p>
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
                "MEBEL",
                "MUDDATLI TO'LOV",
                "BEPUL YETKAZISH",
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
