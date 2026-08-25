import { site } from "@/data/site";

/**
 * Scene 06b — "Sizda yo'q narsani ham olsam bo'ladimi?"
 *
 * The strongest thing the business does that the site did not say anywhere: the
 * installment offer is not limited to the stock on the shelves. Someone who has
 * already found what they want somewhere else can still buy it here, on terms.
 *
 * That changes what the visitor is looking at — not a catalogue to browse, but
 * a way to pay for whatever they have already decided on. It earns its own
 * section rather than a line in the advantages list, and sits directly after
 * the installment steps because it is an extension of them.
 *
 * The copy states only what the owner confirmed: goods the shop does not stock
 * can be arranged on installment. No limit, no fee and no timescale is claimed,
 * because none has been given — the exact terms come from the phone call, the
 * same rule the installment section already follows.
 */
const steps = [
  {
    index: "01",
    title: "Mahsulotni toping",
    detail:
      "Boshqa do'konda, bozorda yoki internetda ko'rgan bo'lsangiz — o'shanisi bo'laveradi.",
  },
  {
    index: "02",
    title: "Bizga ayting",
    detail: "Mahsulot nomi, narxi va qayerda ko'rganingizni aytsangiz kifoya.",
  },
  {
    index: "03",
    title: "Muddatli to'lovga rasmiylashtiramiz",
    detail: "Shartlarni aniqlaymiz va mahsulotni siz uchun muddatli to'lovga olib beramiz.",
  },
];

export function SpecialOrder() {
  return (
    <section
      id="maxsus-buyurtma"
      aria-labelledby="special-title"
      className="on-purple bg-purple py-16 text-white sm:py-24"
    >
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div className="max-w-3xl">
          <p className="kicker text-yellow">Bizda yo&apos;q bo&apos;lsa ham</p>
          <h2 id="special-title" className="display mt-3 text-3xl leading-[1.08] sm:text-5xl">
            Boshqa joyda ko&apos;rgan mahsulotni ham{" "}
            <span className="text-yellow">muddatli to&apos;lovga</span> olasiz
          </h2>
          <p className="mt-6 text-lg leading-relaxed text-on-purple-2">
            Muddatli to&apos;lov faqat do&apos;konimizdagi mahsulotlar uchun emas. Kerakli
            narsani boshqa joyda topgan bo&apos;lsangiz ham, uni siz uchun rasmiylashtirib
            beramiz — naqd pul yig&apos;ib yurish shart emas.
          </p>
        </div>

        <ol data-reveal className="mt-14 grid gap-8 sm:grid-cols-3 sm:gap-6">
          {steps.map((step) => (
            <li key={step.index}>
              <span className="numeral flex h-14 w-14 items-center justify-center rounded-full bg-yellow text-2xl text-ink">
                {step.index}
              </span>
              <h3 className="display mt-5 text-xl">{step.title}</h3>
              <p className="mt-2 leading-relaxed text-on-purple-2">{step.detail}</p>
            </li>
          ))}
        </ol>

        <div data-reveal className="mt-12 flex flex-col gap-5 sm:flex-row sm:items-center">
          <a href="#ariza" className="btn btn-yellow">
            Mahsulotni ayting
          </a>
          <a href={`tel:${site.phone}`} className="btn btn-outline">
            {site.phoneDisplay}
          </a>
          <p className="text-sm leading-relaxed text-on-purple-2 sm:max-w-sm">
            Shartlar mahsulot narxiga qarab aniqlanadi — qo&apos;ng&apos;iroqda aytamiz.
          </p>
        </div>
      </div>
    </section>
  );
}
