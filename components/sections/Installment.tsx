import { installmentSteps } from "@/data/installment";
import { site } from "@/data/site";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * Scene 06 — "Qanday xarid qilaman?"
 *
 * The four steps are the in-store flow; the two hard facts (24 months, passport
 * + card) are verified. Exact monthly figures depend on the product and term,
 * so the section points at a phone number instead of publishing a rate.
 */
export function Installment() {
  return (
    <section
      id="muddatli-tolov"
      aria-labelledby="installment-title"
      className="bg-ground py-16 sm:py-24"
    >
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading
          id="installment-title"
          align="center"
          kicker="Muddatli to'lov"
          title={
            <>
              Bugun oling.{" "}
              <span className="relative z-0">
                <span className="mark">Qulay muddatda</span>
              </span>{" "}
              to&apos;lang.
            </>
          }
          lead={`${site.facts.installmentMonthsMax} oygacha muddatli to'lov. Rasmiylashtirish uchun pasport va plastik kartaning o'zi kifoya — kafil ham, ma'lumotnoma ham kerak emas.`}
        />

        <ol data-reveal className="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
          {installmentSteps.map((step, i) => (
            <li key={step.index} className="relative">
              {/* Connector rail — decorative, hidden from assistive tech. */}
              {i < installmentSteps.length - 1 ? (
                <span
                  aria-hidden="true"
                  className="absolute left-16 right-0 top-7 hidden h-0.5 bg-purple-100 lg:block"
                />
              ) : null}
              <span className="numeral relative flex h-14 w-14 items-center justify-center rounded-full bg-purple text-2xl text-white">
                {step.index}
              </span>
              <h3 className="display mt-5 text-xl">{step.title}</h3>
              <p className="mt-2 leading-relaxed text-ink-2">{step.detail}</p>
            </li>
          ))}
        </ol>

        <div data-reveal className="mt-14 flex justify-center">
          <p className="max-w-xl rounded-2xl bg-ground-2 px-7 py-6 text-center leading-relaxed text-ink-2">
            Oylik to&apos;lov miqdori mahsulot narxi va tanlangan muddatga qarab hisoblanadi.
            Aniq shartlarni bilish uchun qo&apos;ng&apos;iroq qiling:{" "}
            <a
              href={`tel:${site.phone}`}
              className="font-semibold text-purple underline-offset-4 hover:underline"
            >
              {site.phoneDisplay}
            </a>
          </p>
        </div>
      </div>
    </section>
  );
}
