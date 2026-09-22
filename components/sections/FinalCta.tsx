import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { Logo } from "@/components/ui/Logo";

/**
 * Footer oldidagi yakuniy chaqiriq: binafsha fon, bitta sariq tugma — ariza
 * formasiga (`#ariza`). Ikkinchi yo'l — qo'ng'iroq. Matn admin panelda
 * (`texts.finalCtaLead`).
 */
export function FinalCta() {
  return (
    <section id="hamkor-boling" aria-labelledby="cta-title" className="bg-ground py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div
          data-reveal
          className="on-purple relative overflow-hidden rounded-[28px] bg-purple px-6 py-14 text-center text-white sm:px-12 sm:py-20"
        >
          <Logo
            variant="mark"
            className="pointer-events-none absolute -right-10 -top-10 h-64 w-auto text-white/[0.06] sm:h-80"
          />
          <div className="relative mx-auto max-w-2xl">
            <h2 id="cta-title" className="display text-3xl leading-tight sm:text-5xl">
              Kerakli mahsulotni birga topamiz
            </h2>
            <p className="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-on-purple-2">
              <Rich text={content.texts.finalCtaLead} strongClass="font-semibold text-white" />
            </p>
            <div className="mt-9 flex flex-wrap items-center justify-center gap-3">
              <a href="#ariza" className="btn btn-yellow btn-lift">
                Ariza qoldirish
                <span className="nudge" aria-hidden="true">&rarr;</span>
              </a>
              <a href={`tel:${site.phone}`} className="btn btn-outline">
                {site.phoneDisplay}
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
