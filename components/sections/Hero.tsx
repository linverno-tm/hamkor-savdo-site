import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { HeroLead } from "@/components/sections/HeroLead";
import { CategoryCards } from "@/components/sections/Categories";

/**
 * Scene 01 — "Bu kim va menga nima beradi?"
 *
 * Chapda va'da: nima sotiladi, qanday shartda. Ikki yo'l: mahsulotlarni
 * ko'rish (pastdagi kategoriyalar) yoki eng yaqin filialni topish. O'ngda
 * saytning asl maqsadi — ixcham ariza formasi (HeroLead): muddatli to'lovda
 * mijoz saytdan xarid qilmaydi, qo'ng'iroq kutadi.
 *
 * Fon — oq, ustida juda yengil binafsha nur (globals.css > .hero-glow).
 * Kirish animatsiyasi — CSS `rise` va sarlavhaning `.line-mask` qatorlari;
 * JavaScript bo'lmasa ham hammasi ko'rinadi.
 */
export function Hero() {
  return (
    <section id="hero" aria-labelledby="hero-title" className="hero-glow relative overflow-hidden pt-28 sm:pt-32">
      <div className="relative mx-auto max-w-7xl px-5 pb-16 sm:px-8 lg:pb-24">
        <div className="grid items-center gap-12 lg:grid-cols-[1.15fr_0.85fr] lg:gap-16">
          <div>
            <p
              className="rise inline-flex items-center gap-2 rounded-full border border-line bg-ground px-4 py-2 text-sm font-medium text-ink-2"
              style={{ ["--i" as string]: 0 }}
            >
              <span className="h-2 w-2 rounded-full bg-yellow" aria-hidden="true" />
              {site.deliveryArea} yetkazamiz · {site.facts.branchCount} ta filial
            </p>

            <h1
              id="hero-title"
              className="display mt-6 text-[2.6rem] leading-[1.04] sm:text-6xl lg:text-[4.25rem]"
            >
              <span className="line-mask">
                <span style={{ ["--i" as string]: 1 }}>Oilangiz uchun</span>
              </span>
              <span className="line-mask">
                <span style={{ ["--i" as string]: 2 }}>
                  ishonchli <span className="text-purple">hamkor</span>
                </span>
              </span>
            </h1>

            <p
              className="rise mt-6 max-w-xl text-lg leading-relaxed text-ink-2 sm:text-xl"
              style={{ ["--i" as string]: 3 }}
            >
              <Rich text={content.texts.heroLead} />
            </p>

            <div className="rise mt-9 flex flex-wrap gap-3" style={{ ["--i" as string]: 4 }}>
              <a href="#yonalishlar" className="btn btn-primary btn-lift">
                Mahsulotlarni ko&apos;rish
                <span className="nudge" aria-hidden="true">&rarr;</span>
              </a>
              <a href="#filiallar" className="btn btn-outline btn-lift">
                Eng yaqin filialni topish
              </a>
            </div>

            {/* Do'konning o'ziga xos va'dasi — alohida, sariq. */}
            <a
              href="#maxsus-buyurtma"
              className="rise group tap btn-lift mt-6 flex max-w-lg items-center gap-3 rounded-[16px] border-2 border-yellow bg-yellow/10 px-4 py-3 hover:bg-yellow/20"
              style={{ ["--i" as string]: 5 }}
            >
              <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-yellow text-lg" aria-hidden="true">
                ✓
              </span>
              <span className="text-sm font-semibold leading-snug text-ink sm:text-base">
                Bizda yo&apos;qmi? Baribir <span className="text-purple">muddatli to&apos;lovga</span> olib beramiz{" "}
                <span className="nudge" aria-hidden="true">&rarr;</span>
              </span>
            </a>
          </div>

          <div className="rise" style={{ ["--i" as string]: 6 }}>
            <HeroLead />
          </div>
        </div>

        <div className="rise mt-12 lg:mt-16" style={{ ["--i" as string]: 7 }}>
          <CategoryCards />
        </div>
      </div>
    </section>
  );
}
