import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { Logo } from "@/components/ui/Logo";

/**
 * Scene 06b — "Sizda yo'q narsani ham olsam bo'ladimi?"
 *
 * The strongest thing the business does that the site did not say anywhere: the
 * installment offer is not limited to the stock on the shelves. Someone who has
 * already found what they want elsewhere can still buy it here, on terms.
 *
 * The first version of this section lost that message by being shaped like its
 * neighbour — the installment steps directly above are four numbered circles in
 * a row, and this repeated the pattern with three. Adjacent and near-identical,
 * the eye reads the second one as "more of the same" and skips it.
 *
 * So this one deliberately breaks the page's rhythm instead of joining it:
 * centred where every other section is left-aligned, one short question and its
 * answer at display size, and the three steps demoted to a single quiet line.
 * A reader who is scanning rather than reading gets the whole offer from two
 * lines — which is the point. It stays a normal section otherwise, with the
 * same padding and colours as the rest, so it catches the eye without
 * shouting over the sections around it.
 *
 * The copy claims only what the owner confirmed: goods the shop does not stock
 * can be arranged on installment. No limit, fee or timescale is stated, because
 * none has been given — exact terms come from the phone call, the same rule the
 * installment section follows.
 */
const steps = ["Mahsulotni toping", "Nomi va narxini ayting", "Rasmiylashtiramiz"];

export function SpecialOrder() {
  return (
    <section
      id="maxsus-buyurtma"
      aria-labelledby="special-title"
      className="on-purple relative overflow-hidden bg-purple py-16 text-white sm:py-24"
    >
      {/* Brend monogrammasi — filial sahifalarida ishlatilgan usul. Fon uchun
          tayyor shakl, yangi bezak o'ylab topilmaydi. */}
      <Logo
        variant="mark"
        className="pointer-events-none absolute -right-8 top-1/2 hidden h-80 w-auto -translate-y-1/2 text-white/[0.07] lg:block"
      />

      {/* max-w-4xl: sarlavha ikki qatorga sig'sin. Torroq bo'lsa "olasiz."
          uchinchi qatorda yolg'iz qoladi va zarba yo'qoladi. */}
      <div className="relative mx-auto max-w-4xl px-5 text-center sm:px-8">
        <p className="kicker text-yellow">Bizda yo&apos;q bo&apos;lsa ham</p>

        {/* Savol va javob. Ikki qatorda butun taklif tushunarli bo'lishi kerak —
            odam qolganini o'qimasa ham. */}
        <h2
          id="special-title"
          className="display mt-4 text-4xl leading-[1.05] sm:text-5xl lg:text-6xl"
        >
          Bizda yo&apos;qmi?
          <br />
          Baribir <span className="text-yellow">muddatli to&apos;lovga</span> olasiz.
        </h2>

        <p className="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-on-purple-2">
          <Rich text={content.texts.specialOrderLead} strongClass="font-semibold text-white" />
        </p>

        {/* Uch qadam — endi bitta jimgina qator. Ma'lumot saqlanadi, lekin
            yuqoridagi bo'limning raqamli doiralarini takrorlamaydi. */}
        <ol className="mt-9 flex flex-wrap items-center justify-center gap-x-3 gap-y-2 text-sm font-semibold">
          {steps.map((step, i) => (
            <li key={step} className="flex items-center gap-3">
              {i > 0 ? (
                <span className="text-yellow" aria-hidden="true">
                  &rarr;
                </span>
              ) : null}
              {step}
            </li>
          ))}
        </ol>

        <div className="mt-10 flex flex-wrap justify-center gap-3">
          <a href="#ariza" className="btn btn-yellow">
            Mahsulotni ayting
          </a>
          <a href={`tel:${site.phone}`} className="btn btn-outline">
            {site.phoneDisplay}
          </a>
        </div>

        <p className="mt-6 text-sm text-on-purple-2">
          Shartlar mahsulot narxiga qarab aniqlanadi — qo&apos;ng&apos;iroqda aytamiz.
        </p>
      </div>
    </section>
  );
}
