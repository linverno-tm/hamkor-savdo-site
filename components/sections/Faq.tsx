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
        <div data-reveal className="mt-12 space-y-3">
          {items.map((f, i) => (
            <details key={i} className="faq-item group rounded-[18px] border border-line bg-ground px-5 transition-colors open:border-purple-100 sm:px-6">
              <summary className="flex min-h-14 cursor-pointer list-none items-center justify-between gap-6 py-4 text-lg font-semibold text-ink [&::-webkit-details-marker]:hidden">
                {fill(f.q)}
                <span
                  aria-hidden="true"
                  className="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-purple-50 text-xl leading-none text-purple transition-transform duration-300 group-open:rotate-45"
                >
                  +
                </span>
              </summary>
              <p className="whitespace-pre-line pb-5 leading-relaxed text-ink-2">{fill(f.a)}</p>
            </details>
          ))}
        </div>
      </div>
    </section>
  );
}
