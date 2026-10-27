import { content, fill } from "@/lib/content";
import { ldJson } from "@/lib/seo";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * Savol-javob — admin paneldan. Native <details>, JavaScript kerak emas.
 * FAQPage schema bilan: Google javobni qidiruv natijasida ko'rsatishi mumkin.
 *
 * Kompyuterda: chapda sarlavha joyida turadi, o'ngda savollar. Ochilish
 * silliq (`::details-content`, qo'llaydigan brauzerda), "+" belgisi "×"
 * ga aylanadi.
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
    <section id="savollar" aria-labelledby="faq-title" className="sec faq">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: ldJson(jsonLd) }} />
      <div className="wrap faq-grid">
        <div className="faq-side">
          <div className="faq-sticky">
            <SectionHeading id="faq-title" kicker="Savol-javob" title="Ko'p so'raladigan savollar" />
          </div>
        </div>
        <div data-m="" data-m-stagger className="faq-list">
          {items.map((f, i) => (
            <details key={i} className="faq-item">
              <summary className="faq-q">
                <span>{fill(f.q)}</span>
                <span aria-hidden="true" className="faq-plus" />
              </summary>
              <p className="faq-a">{fill(f.a)}</p>
            </details>
          ))}
        </div>
      </div>
    </section>
  );
}
