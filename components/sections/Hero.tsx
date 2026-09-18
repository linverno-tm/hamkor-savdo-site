import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { Logo } from "@/components/ui/Logo";

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

  /* Bosh sahifada navbat bilan almashadigan suratlar. Beshtasi ataylab:
     tilla, mebel, texnika va skuterlar — do'konning uchala yo'nalishi — va
     oxirida haqiqiy mijozlar. Birinchisi eng kuchli kadr, chunki sahifa
     ochilganda o'sha ko'rinadi (va LCP elementi ham o'sha). */
  const heroPhotos = [
    {
      src: "/filiallar/andijon-amir-temur/zal-1",
      alt: "HAMKOR SAVDO savdo zali — tilla buyumlari peshtaxtasi, orqasida mebel va maishiy texnika",
    },
    {
      src: "/filiallar/shahrixon-ozodbek/zal-4",
      alt: "Mebel bo'limi — yumshoq burchak va jurnal stoli",
    },
    {
      src: "/filiallar/asaka-umid/zal-1",
      alt: "Maishiy texnika bo'limi — muzlatgichlar, kir yuvish mashinalari va bolalar transporti",
    },
    {
      src: "/filiallar/shahrixon-ozodbek/zal-1",
      alt: "Zargarlik peshtaxtasi — tilla taqinchoqlar va quyma tilla",
    },
    {
      src: "/filiallar/shahrixon-ozodbek/mijoz-1",
      alt: "Mijozlar velosiped va bolalar aravachalari bo'limida tanlamoqda",
    },
  ];

  return (
    <section id="hero" aria-labelledby="hero-title" className="relative overflow-hidden pt-28 sm:pt-32">
      {/* Juda keng ekranlarda mazmun 1600px ustunda qolib, ikki chetda katta
          oq maydon paydo bo'ladi. Ustunni kengaytirish yechim emas — matn
          qatori uzayib o'qish qiyinlashadi. O'rniga chetga brend belgisi
          qo'yiladi.
          Chegara 2200px: aynan shundan keyin chetdagi bo'shliq 300px dan
          oshadi va belgi butunlay sig'adi. Pastroq chegarada u yo mazmun
          ostiga kirib qolardi, yo ekran chetidan kesilib, ataylab emas,
          xato qo'yilgandek ko'rinardi. */}
      <Logo
        variant="mark"
        aria-hidden="true"
        className="pointer-events-none absolute left-12 top-64 hidden h-56 w-auto text-purple-50 min-[2200px]:block"
      />
      <Logo
        variant="mark"
        aria-hidden="true"
        className="pointer-events-none absolute right-12 top-40 hidden h-40 w-auto text-purple-50 min-[2200px]:block"
      />
      <div className="relative mx-auto max-w-7xl px-5 pb-20 sm:px-8 lg:pb-28">
        <div className="grid items-start gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
          <div>
            <p
              className="rise inline-flex items-center gap-2 rounded-full border border-line bg-ground px-4 py-2 text-sm font-medium text-ink-2"
              style={{ ["--i" as string]: 0 }}
            >
              <span className="h-2 w-2 rounded-full bg-yellow" aria-hidden="true" />
              {site.deliveryArea} yetkazamiz · {site.facts.branchCount} ta filial
            </p>

            {/* Har bir qator o'z darchasidan ko'tarilib chiqadi (.line-mask).
                Ilgari butun sarlavha birdan paydo bo'lardi; qatorlab chiqishi
                sokinroq va qimmatroq ko'rinadi. Qatorlar endi blok bo'lgani
                uchun <br> kerak emas — telefonda ham, kompyuterda ham
                bir xil ikki qator. */}
            <h1
              id="hero-title"
              className="display mt-6 text-[2.75rem] leading-[1.02] sm:text-6xl lg:text-7xl"
            >
              <span className="line-mask">
                <span style={{ ["--i" as string]: 1 }}>Oilangizga</span>
              </span>
              <span className="line-mask">
                <span style={{ ["--i" as string]: 2 }}>
                  ishonchli <span className="text-purple">hamkor</span>
                </span>
              </span>
            </h1>

            <p
              className="rise mt-6 max-w-xl text-lg leading-relaxed text-ink-2 sm:text-xl"
              style={{ ["--i" as string]: 3 }}
            >
              <Rich text={content.texts.heroLead} />
            </p>

            <div className="rise mt-9 flex flex-col gap-3" style={{ ["--i" as string]: 4 }}>
              <div className="flex flex-wrap gap-3">
                <a href="#filiallar" className="btn btn-primary btn-lift">
                  Eng yaqin filialni toping
                  <span className="nudge" aria-hidden="true">&rarr;</span>
                </a>
                <a href="#ariza" className="btn btn-outline btn-lift">
                  Ariza qoldirish
                </a>
              </div>

              <a
                href="#maxsus-buyurtma"
                className="group tap btn-lift flex max-w-md items-center gap-3 rounded-2xl border-2 border-yellow bg-yellow/10 px-4 py-3 hover:bg-yellow/20"
              >
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-yellow text-lg" aria-hidden="true">
                  ✓
                </span>
                <span className="text-sm font-semibold leading-snug text-ink sm:text-base">
                  Bizda yo&apos;qmi? Baribir <span className="text-purple">muddatli to&apos;lovga</span> olib beramiz <span className="nudge" aria-hidden="true">&rarr;</span>
                </span>
              </a>
            </div>
          </div>

          {/* The shop, with the quick-answer panel overlapping its lower edge.
              The overlap is a negative margin, never a transform: a transformed
              element becomes the containing block for its `position: fixed`
              descendants, which is what once trapped the photo lightbox inside
              <main> instead of the viewport. */}
          <div className="rise relative" style={{ ["--i" as string]: 5 }}>
            {/* Ilgari bu yerda menejerlar kompyuter oldida o'tirgan bitta
                surat turardi. U noto'g'ri gap aytardi: birinchi kadrda hujjat
                va rasmiylashtirish ko'rinardi, mahsulot esa yo'q edi.

                Endi beshta kadr navbat bilan almashadi — bitta surat butun
                do'konni ko'rsata olmaydi. Nisbat 4/3, ya'ni suratlar qanday
                olingan bo'lsa shundayligicha turadi: 4/2.7 ga kesilganda
                peshtaxtaning pastki qismi qirqilib qolardi. */}
            <div className="hero-parallax hero-cycle aspect-[4/3] w-full overflow-hidden rounded-[28px]">
              {heroPhotos.map((p, i) => (
                <img
                  key={p.src}
                  src={`${p.src}-960.webp`}
                  srcSet={`${p.src}-480.webp 480w, ${p.src}-960.webp 960w`}
                  sizes="(max-width: 1024px) 100vw, 700px"
                  width={960}
                  height={720}
                  alt={p.alt}
                  className="object-cover"
                  /* Hech biri `lazy` emas: birinchisidan keyingisi 5 soniyada
                     kerak bo'ladi, kechiktirilgan surat esa o'z navbatida
                     bo'sh kadr bo'lib chiqadi — birinchi urinishda aynan
                     shunday bo'ldi. Buning o'rniga birinchisi yuqori
                     ustuvorlik bilan (u LCP elementi), qolganlari past
                     ustuvorlik bilan yuklanadi: sahifaning ochilish tezligiga
                     xalaqit bermaydi, lekin o'z vaqtida tayyor turadi. */
                  loading="eager"
                  fetchPriority={i === 0 ? "high" : "low"}
                  decoding="async"
                />
              ))}
            </div>

            <div className="stat-card on-purple relative z-10 -mt-12 rounded-[28px] bg-purple p-7 text-white sm:-mt-16 sm:ml-10 sm:p-9">
              <p className="kicker">Qisqacha</p>
              <dl className="mt-6 grid grid-cols-2 gap-x-6 gap-y-7">
                {facts.map((f) => (
                  /* flex-col-reverse keeps <dt> before <dd> in the DOM (as the spec
                     requires) while showing the number above its label. */
                  <div key={f.label} className="flex flex-col-reverse gap-2">
                    <dt className="text-sm leading-snug text-on-purple-2">
                      {f.label}
                      {/* Izoh ham to'liq `on-purple-2` rangda: 70% shaffoflikda
                          binafsha fonga nisbatan kontrast 3.35:1 edi, ya'ni
                          mayda matn uchun WCAG AA (4.5:1) dan past. */}
                      {f.note ? <span className="mt-0.5 block text-xs text-on-purple-2">{f.note}</span> : null}
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
                /* Yetkazish va o'rnatish bitta bandda: "bepul" ikkalasiga
                   ham faqat `freeDeliveryArea` ichida taalluqli, shuning
                   uchun hudud har ikkalasi uchun bir marta yoziladi.
                   Filial soni ham admin paneldagi ro'yxatdan sanaladi. */
                `BEPUL YETKAZISH VA O'RNATISH — ${site.freeDeliveryArea.toUpperCase()}`,
                `${site.facts.branchCount} TA FILIAL`,
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
