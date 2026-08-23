import { categories } from "@/data/categories";
import { site } from "@/data/site";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * Scene 04 — "Nimalar bor?" The three verified directions.
 * Each panel is a real link target (`#yonalish-<id>`) so the jump pills give a
 * direct path without scrolling through the whole page.
 */
export function Categories() {
  return (
    <section id="yonalishlar" aria-labelledby="categories-title" className="bg-ground py-24 sm:py-32">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading
          id="categories-title"
          kicker="Yo'nalishlar"
          title={
            <>
              Bitta do&apos;konda{" "}
              <span className="relative z-0">
                <span className="mark">uch yo&apos;nalish</span>
              </span>
            </>
          }
          lead={`Uy uchun kerakli hamma narsa — ${site.facts.productCount} mahsulot orasidan tanlaysiz.`}
        />

        <nav aria-label="Yo'nalishlarga o'tish" className="mt-9 flex flex-wrap gap-2">
          {categories.map((c) => (
            <a
              key={c.id}
              href={`#yonalish-${c.id}`}
              className="numeral rounded-full border-2 border-purple-100 px-5 py-2 text-lg tracking-[0.12em] text-purple transition-colors hover:border-purple hover:bg-purple hover:text-white"
            >
              {c.name}
            </a>
          ))}
        </nav>

        <div data-reveal className="mt-10 grid gap-5 lg:grid-cols-3">
          {categories.map((c) => (
            <article
              key={c.id}
              id={`yonalish-${c.id}`}
              className="card card-hover group scroll-mt-28 overflow-hidden"
            >
              <div className="flex items-start justify-between gap-4 bg-purple-50 px-7 pb-6 pt-7">
                <div>
                  <span className="numeral text-sm tracking-[0.3em] text-ink-3" aria-hidden="true">
                    {c.index}
                  </span>
                  <h3 className="numeral mt-2 text-5xl tracking-[0.04em] text-purple">{c.name}</h3>
                </div>
                <span
                  aria-hidden="true"
                  className="mt-1 h-3 w-10 shrink-0 rounded-full bg-yellow transition-all duration-300 group-hover:w-16"
                />
              </div>
              <div className="px-7 pb-7 pt-6">
                <p className="font-semibold text-ink">{c.kicker}</p>
                <p className="mt-3 leading-relaxed text-ink-2">{c.description}</p>
                <a
                  href="#filiallar"
                  className="mt-6 inline-flex items-center gap-2 font-semibold text-purple underline-offset-4 hover:underline"
                >
                  Filialda ko&apos;rish
                  <span aria-hidden="true">→</span>
                </a>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
