import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { Logo } from "@/components/ui/Logo";

/**
 * Scene 10 — "Keyingi qadam nima?" The story lands on one action: come to a
 * branch or call. Conversion here is a visit, not a checkout.
 */
export function FinalCta() {
  return (
    <section
      id="hamkor-boling"
      aria-labelledby="cta-title"
      className="on-purple relative overflow-hidden bg-purple py-16 text-white sm:py-24"
    >
      <Logo
        variant="mark"
        className="pointer-events-none absolute left-1/2 top-1/2 h-56 w-auto -translate-x-1/2 -translate-y-1/2 text-white/[0.07] sm:h-80 lg:h-[26rem]"
      />

      <div data-reveal className="relative mx-auto max-w-3xl px-5 text-center sm:px-8">
        <p className="kicker">Keyingi qadam</p>
        <h2 id="cta-title" className="display mt-4 text-5xl sm:text-6xl lg:text-7xl">
          HAMKOR BO&apos;LING
        </h2>
        <p className="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-on-purple-2">
          <Rich text={content.texts.finalCtaLead} strongClass="font-semibold text-white" />
        </p>
        <div className="mt-9 flex flex-wrap items-center justify-center gap-3">
          <a href={`tel:${site.phone}`} className="btn btn-yellow">
            {site.phoneDisplay}
          </a>
          <a href="#filiallar" className="btn btn-outline">
            Filiallar
          </a>
          <a
            href={site.telegramUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="btn btn-outline"
          >
            Telegram
          </a>
        </div>
      </div>
    </section>
  );
}
