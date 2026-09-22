import Link from "next/link";
import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { Logo } from "@/components/ui/Logo";
import { HeroLead } from "@/components/sections/HeroLead";

/**
 * Scene 01 — "Bu kim va menga nima beradi?"
 *
 * Chapda va'da: nima sotiladi, qanday shartda, qayerda. O'ngda saytning
 * maqsadi — qisqa ariza formasi (components/sections/HeroLead.tsx).
 *
 * Ilgari o'ngda suratlar va raqamlar kartochkasi turardi. Kartochkadagi
 * uchta raqam pastdagi "Ko'lam" bo'limida aynan takrorlanardi, suratlar
 * esa "Biz haqimizda" va filial sahifalarida bor. Muddatli to'lovda mijoz
 * saytdan xarid qilmaydi — qo'ng'iroq kutadi, shuning uchun eng ko'p e'tibor
 * tushadigan joy formaga berildi.
 *
 * Kirish animatsiyasi — CSS `rise`, har bandga o'z kechikishi bilan; sarlavha
 * esa qatorlab, `.line-mask` darchasidan ko'tariladi. Hech biri mazmunni
 * to'sib turmaydi: JavaScript bo'lmasa ham hammasi ko'rinadi.
 */
/**
 * "Nima sotiladi" — birinchi ekranda ko'z bilan. Matnni o'qimagan odam ham
 * bir qarashda do'kon nima sotishini ko'radi; har biri o'z sahifasiga olib
 * boradi. Skuter surati Pexels'dan (bepul litsenziya, muallif ko'rsatilishi
 * shart emas): https://www.pexels.com/photo/15675779/ — do'konning o'z
 * skuter surati bo'lsa, public/yonalishlar/skuter-*.webp ni almashtiring.
 */
const HERO_TOPICS = [
  { href: "/texnika", label: "Texnika", photo: "/yonalishlar/texnika-480.webp" },
  { href: "/tilla", label: "Tilla", photo: "/yonalishlar/tilla-480.webp" },
  { href: "/mebel", label: "Mebel", photo: "/yonalishlar/mebel-480.webp" },
  { href: "/skuter", label: "Skuterlar", photo: "/yonalishlar/skuter-480.webp" },
];

export function Hero() {
  return (
    <section id="hero" aria-labelledby="hero-title" className="relative overflow-hidden pt-28 sm:pt-32">
      {/* Juda keng ekranlarda mazmun 1600px ustunda qolib, ikki chetda katta
          oq maydon paydo bo'ladi. Ustunni kengaytirish yechim emas — matn
          qatori uzayib o'qish qiyinlashadi. O'rniga chetga brend belgisi
          qo'yiladi.
          Chegara 2200px: aynan shundan keyin chetdagi bo'shliq 300px dan
          oshadi va belgi butunlay sig'adi. Pastroq chegarada u yo mazmun
          ostiga kirib qolardi, yo ekran chetidan kesilib, ataylab emas,
          xato qo'yilgandek ko'rinardi. */}
      <Logo
        variant="mark"
        aria-hidden="true"
        className="pointer-events-none absolute left-12 top-64 hidden h-56 w-auto text-purple-50 min-[2200px]:block"
      />
      <Logo
        variant="mark"
        aria-hidden="true"
        className="pointer-events-none absolute right-12 top-40 hidden h-40 w-auto text-purple-50 min-[2200px]:block"
      />
      <div className="relative mx-auto max-w-7xl px-5 pb-20 sm:px-8 lg:pb-28">
        <div className="grid items-start gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
          <div>
            <p
              className="rise inline-flex items-center gap-2 rounded-full border border-line bg-ground px-4 py-2 text-sm font-medium text-ink-2"
              style={{ ["--i" as string]: 0 }}
            >
              <span className="h-2 w-2 rounded-full bg-yellow" aria-hidden="true" />
              {site.deliveryArea} yetkazamiz · {site.facts.branchCount} ta filial
            </p>

            {/* Har bir qator o'z darchasidan ko'tarilib chiqadi (.line-mask).
                Ilgari butun sarlavha birdan paydo bo'lardi; qatorlab chiqishi
                sokinroq va qimmatroq ko'rinadi. Qatorlar endi blok bo'lgani
                uchun <br> kerak emas — telefonda ham, kompyuterda ham
                bir xil ikki qator. */}
            <h1
              id="hero-title"
              className="display mt-6 text-[2.75rem] leading-[1.02] sm:text-6xl lg:text-7xl"
            >
              <span className="line-mask">
                <span style={{ ["--i" as string]: 1 }}>Oilangizga</span>
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

            <div className="rise mt-9 flex flex-col gap-3" style={{ ["--i" as string]: 4 }}>
              <div className="flex flex-wrap gap-3">
                <a href="#filiallar" className="btn btn-primary btn-lift">
                  Eng yaqin filialni toping
                  <span className="nudge" aria-hidden="true">&rarr;</span>
                </a>
                {/* Ilgari bu yerda "Ariza qoldirish" turardi va sahifaning
                    pastiga sakrardi — endi forma yonginasida, ya'ni tugma
                    mijozni undan uzoqlashtirardi. O'rniga ikkinchi yo'l:
                    yozishni xohlamagan odam darhol qo'ng'iroq qiladi. */}
                <a href={`tel:${site.phone}`} className="btn btn-outline btn-lift">
                  {site.phoneDisplay}
                </a>
              </div>

              <a
                href="#maxsus-buyurtma"
                className="group tap btn-lift flex max-w-md items-center gap-3 rounded-2xl border-2 border-yellow bg-yellow/10 px-4 py-3 hover:bg-yellow/20"
              >
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-yellow text-lg" aria-hidden="true">
                  ✓
                </span>
                <span className="text-sm font-semibold leading-snug text-ink sm:text-base">
                  Bizda yo&apos;qmi? Baribir <span className="text-purple">muddatli to&apos;lovga</span> olib beramiz <span className="nudge" aria-hidden="true">&rarr;</span>
                </span>
              </a>
            </div>

            <nav aria-label="Nima sotamiz" className="rise mt-8" style={{ ["--i" as string]: 5 }}>
              <ul className="grid max-w-xl grid-cols-4 gap-2 sm:gap-3">
                {HERO_TOPICS.map((t) => (
                  <li key={t.href}>
                    <Link
                      href={t.href}
                      className="group btn-lift relative block aspect-[4/5] overflow-hidden rounded-2xl bg-ground-2"
                    >
                      <img
                        src={t.photo}
                        alt=""
                        width={480}
                        height={360}
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        decoding="async"
                      />
                      <span className="absolute inset-x-0 bottom-0 px-2 pb-2 pt-8 text-center text-xs font-semibold text-white [background:linear-gradient(to_top,rgba(26,17,48,0.85),transparent)] sm:text-sm">
                        {t.label}
                      </span>
                    </Link>
                  </li>
                ))}
              </ul>
            </nav>
          </div>

          {/* O'ng ustun: qisqa ariza formasi.
              Ilgari bu yerda suratlar va raqamlar kartochkasi turardi.
              Raqamlar pastdagi "Ko'lam" bo'limida allaqachon bor edi, ya'ni
              takrorlanardi; suratlar esa "Biz haqimizda" va filial
              sahifalarida qoladi. Muddatli to'lovda mijoz saytdan xarid
              qilmaydi — qo'ng'iroq kutadi, shuning uchun eng ko'rinadigan
              joyni saytning maqsadi egallaydi. */}
          <div className="rise" style={{ ["--i" as string]: 6 }}>
            <HeroLead />
          </div>
        </div>
      </div>

      {/* Brand marquee — the guidebook's bold, confident voice, as one CSS keyframe. */}
      <div className="marquee overflow-hidden border-y border-line bg-ground-2 py-4" aria-hidden="true">
        <div className="marquee-track">
          {Array.from({ length: 2 }).map((_, dup) => (
            <div key={dup} className="flex shrink-0 items-center">
              {[
                "TILLA",
                "TEXNIKA",
                "SKUTERLAR",
                "MEBEL",
                "MUDDATLI TO'LOV",
                /* Yetkazish va o'rnatish bitta bandda: "bepul" ikkalasiga
                   ham faqat `freeDeliveryArea` ichida taalluqli, shuning
                   uchun hudud har ikkalasi uchun bir marta yoziladi.
                   Filial soni ham admin paneldagi ro'yxatdan sanaladi. */
                `BEPUL YETKAZISH VA O'RNATISH — ${site.freeDeliveryArea.toUpperCase()}`,
                `${site.facts.branchCount} TA FILIAL`,
              ].map((word) => (
                <span key={word} className="flex shrink-0 items-center">
                  <span className="numeral px-6 text-2xl tracking-[0.12em] text-ink-3">{word}</span>
                  <span className="h-2 w-2 shrink-0 rounded-full bg-yellow" />
                </span>
              ))}
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
