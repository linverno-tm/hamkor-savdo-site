import { content, fill } from "@/lib/content";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * Savol-javob — admin paneldan. Native <details>, JavaScript kerak emas.
 * FAQPage schema bilan: Google javobni qidiruv natijasida ko'rsatishi mumkin.
 */
export function Faq() {
  const items = content.faq.filter((f) => f.q.trim() && f.a.trim());
  if (items.length === 0) return null;

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: items.map((f) => ({
      "@type": "Question",
      name: fill(f.q),
      acceptedAnswer: { "@type": "Answer", text: fill(f.a) },
    })),
  };

  return (
    <section id="savollar" aria-labelledby="faq-title" className="bg-ground-2 py-16 sm:py-24">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      <div className="mx-auto max-w-3xl px-5 sm:px-8">
        <SectionHeading id="faq-title" align="center" kicker="Savol-javob" title="Ko'p so'raladigan savollar" />
        <div data-reveal className="mt-12 divide-y divide-line border-y border-line">
          {items.map((f, i) => (
            <details key={i} className="group py-5">
              <summary className="flex cursor-pointer list-none items-start justify-between gap-6 text-lg font-semibold text-ink">
                {fill(f.q)}
                <span
                  aria-hidden="true"
                  className="mt-1 text-2xl leading-none text-purple transition-transform group-open:rotate-45"
                >
                  +
                </span>
              </summary>
              <p className="mt-3 whitespace-pre-line leading-relaxed text-ink-2">{fill(f.a)}</p>
            </details>
          ))}
        </div>
      </div>
    </section>
  );
}
