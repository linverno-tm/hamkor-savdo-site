import { site } from "@/data/site";

/**
 * Scene 02 — "Ular nima qiladi?"
 *
 * [REAL COMPANY DESCRIPTION REQUIRED] — `site.unpublished.companyStory` is null
 * until the business supplies its own history; the copy below states only what
 * the guidebook, the official Telegram channel and the Instagram bio confirm.
 */
export function About() {
  return (
    <section
      id="biz-haqimizda"
      aria-labelledby="about-title"
      className="relative bg-ground py-16 sm:py-24"
    >
      <div className="mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20">
        <div data-reveal>
          <p className="kicker">Biz haqimizda</p>
          <h2 id="about-title" className="display mt-3 text-4xl sm:text-5xl">
            Biz shunchaki <br className="hidden sm:block" />
            do&apos;kon emasmiz
          </h2>

          {/* Sarlavha ostidagi bo'sh ustunni to'ldiradi va matndagi "tarmoq"
              so'zini ko'rsatib beradi. Ataylab boshqa filialdan olingan surat:
              yonidagi bo'limlarda Andijonning peshtoqi va Shahrixonning
              peshtaxtasi turibdi, bir rasm ikki joyda takrorlanmasin. */}
          <img
            src="/filiallar/andijon-amir-temur/zal-4-960.webp"
            srcSet="/filiallar/andijon-amir-temur/zal-4-480.webp 480w, /filiallar/andijon-amir-temur/zal-4-960.webp 960w"
            sizes="(max-width: 1024px) 100vw, 440px"
            width={960}
            height={720}
            alt="HAMKOR SAVDO savdo maydoni — zargarlik peshtaxtasi va texnika bo'limi bir zalda"
            className="mt-8 block w-full rounded-[28px] object-cover"
            loading="lazy"
            decoding="async"
          />
        </div>

        <div data-reveal className="space-y-6 text-lg leading-relaxed text-ink-2">
          <p>
            HAMKOR SAVDO — Andijon viloyatidagi savdo do&apos;konlari tarmog&apos;i. Shahrixon,
            Asaka va Andijonda jami{" "}
            <strong className="font-semibold text-ink">
              {site.facts.branchCount} ta filial
            </strong>{" "}
            ishlaydi, ularda uch yo&apos;nalish birlashgan:{" "}
            <strong className="font-semibold text-ink">tilla</strong>,{" "}
            <strong className="font-semibold text-ink">texnika</strong> va{" "}
            <strong className="font-semibold text-ink">mebel</strong>.
          </p>
          <p>
            Nomimiz bejiz emas. Katta xaridlar — sovchilik, ko&apos;chish, uy jihozlash — bir kunda
            hal bo&apos;lmaydi. Shuning uchun biz sotuvchi emas,{" "}
            <span className="relative z-0">
              <span className="mark font-semibold text-ink">oilangizning hamkori</span>
            </span>{" "}
            bo&apos;lishga harakat qilamiz: mahsulotni bugun olib ketasiz, to&apos;lovni esa{" "}
            {site.facts.installmentMonthsMax} oygacha bo&apos;lib to&apos;laysiz.
          </p>
          <p>
            Xaridingizni bepul yetkazib beramiz va bepul o&apos;rnatib beramiz — ya&apos;ni
            do&apos;kondan chiqqaningizdan keyin ham yolg&apos;iz qolmaysiz.
          </p>

          {site.unpublished.companyStory ? <p>{site.unpublished.companyStory}</p> : null}

          <div className="flex flex-wrap gap-3 pt-2">
            <a href="#yonalishlar" className="btn btn-primary">
              Yo&apos;nalishlarni ko&apos;rish
            </a>
            <a href="#filiallar" className="btn btn-outline">
              Filiallar
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}
