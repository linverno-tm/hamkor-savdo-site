import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { HeroLead } from "@/components/sections/HeroLead";
import { CategoryCards } from "@/components/sections/Categories";
import { Logo } from "@/components/ui/Logo";

/**
 * Bosh qism — "Bu kim va menga nima beradi?"
 *
 * Binafsha sahna butun ekranni egallaydi: chapda va'da va ikki yo'l
 * (mahsulotlar, eng yaqin filial), o'ngda qisqa ariza (HeroLead). Pastda
 * to'rtta yo'nalish kartasi yelpig'ich bo'lib ko'tariladi va sahifa
 * aylantirilganda tekis qatorga tizilib, sahnadan oq qismga o'tadi
 * (app/home.css > .cats).
 *
 * Fon harakati: uchta sekin suzuvchi nur, katta xira logotip belgisi va
 * kursor ortidan yurgan yorug'lik (`data-spot`, lib/motion.ts). Hammasi
 * bezak — `aria-hidden`, JavaScript'siz ham sahifa to'liq.
 *
 * Kirish: badge → sarlavha qatorlari (darcha ichidan) → matn → tugmalar →
 * forma → kartalar. Bir marta, sahifa ochilganda.
 */
export function Hero() {
  return (
    <section id="hero" aria-labelledby="hero-title" className="hero">
      <div className="hero-stage on-purple" data-spot>
        <div className="hero-bg" aria-hidden="true">
          <span className="hero-orb hero-orb-1" />
          <span className="hero-orb hero-orb-2" />
          <span className="hero-orb hero-orb-3" />
          <span className="hero-spot" />
          <span className="hero-grid" />
          <Logo variant="mark" className="hero-mark" />
        </div>

        <div className="relative mx-auto grid max-w-7xl items-center gap-12 px-5 sm:px-8 lg:grid-cols-[1.15fr_0.85fr] lg:gap-16">
          <div className="text-white">
            <p className="hero-pill in-1">
              <span className="hero-pill-dot" aria-hidden="true" />
              {site.deliveryArea} yetkazamiz · {site.facts.branchCount} ta filial
            </p>

            <h1 id="hero-title" className="hero-title">
              <span className="hero-line">
                <span className="in-2">Oilangiz uchun</span>
              </span>
              <span className="hero-line">
                <span className="in-3">
                  ishonchli <span className="hero-word">hamkor</span>
                </span>
              </span>
            </h1>

            <p className="hero-lead in-4">
              <Rich text={content.texts.heroLead} strongClass="font-semibold text-white" />
            </p>

            <div className="in-5 mt-9 flex flex-wrap gap-3">
              <a href="#yonalishlar" className="btn btn-yellow btn-glow">
                Mahsulotlarni ko&apos;rish
                <span className="nudge" aria-hidden="true">&rarr;</span>
              </a>
              <a href="#filiallar" className="btn btn-outline">
                Eng yaqin filialni topish
              </a>
            </div>

            {/* Do'konning o'ziga xos va'dasi — alohida, sariq. */}
            <a href="#ariza" className="hero-promise in-6 group tap">
              <span className="hero-promise-tick" aria-hidden="true">
                ✓
              </span>
              <span>
                Bizda yo&apos;qmi? Baribir <span className="text-yellow">muddatli to&apos;lovga</span> olib beramiz{" "}
                <span className="nudge" aria-hidden="true">&rarr;</span>
              </span>
            </a>
          </div>

          {/* Kompyuterda doim; telefonda — faqat A/B sinovning "b" guruhida
              (lib/ab.ts > tel-forma, app/home-hero.css > .hero-lead-wrap). */}
          <div className="in-7 hero-lead-wrap">
            <HeroLead />
          </div>
        </div>
      </div>

      <div className="cats-wrap relative mx-auto max-w-7xl px-5 sm:px-8">
        <CategoryCards />
      </div>
    </section>
  );
}
