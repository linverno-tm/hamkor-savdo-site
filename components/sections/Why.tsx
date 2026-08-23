import { advantages } from "@/data/advantages";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * Scene 05 — "Nega aynan ular?" Six verified advantages in plain sentences.
 * No decoding of icons required; the numeral is an index, not a symbol.
 */
export function Why() {
  return (
    <section
      id="afzalliklar"
      aria-labelledby="why-title"
      className="bg-ground-2 py-24 sm:py-32"
    >
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading
          id="why-title"
          align="center"
          kicker="Nega HAMKOR SAVDO?"
          title="Xaridni oson qiladigan olti sabab"
        />

        <div data-reveal className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {advantages.map((a, i) => (
            <div key={a.id} className="card card-hover flex flex-col p-7">
              <span className="numeral text-3xl text-purple-100" aria-hidden="true">
                {String(i + 1).padStart(2, "0")}
              </span>
              <h3 className="display mt-3 text-2xl">{a.title}</h3>
              <p className="mt-3 leading-relaxed text-ink-2">{a.detail}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
