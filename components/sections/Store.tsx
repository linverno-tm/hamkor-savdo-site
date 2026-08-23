import { site } from "@/data/site";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * Scene 08 — "Haqiqiy do'kon qanday?"
 *
 * [REAL STORE PHOTO REQUIRED] × 3 — these frames are deliberate, visibly
 * labelled placeholders for the company's own photography. No stock imagery
 * stands in for a real business. To fill them: drop files into
 * `public/store/` and replace each figure's placeholder block with
 * `<Image src="/store/…" fill sizes="(max-width:768px) 100vw, 33vw" />`.
 * The layout, aspect ratios and captions are already built around them.
 */

const slots = [
  { id: "bino", title: "Do'kon binosi", note: "Filial tashqi ko'rinishi" },
  { id: "zal", title: "Savdo zali", note: "Tilla, texnika va mebel bo'limlari" },
  { id: "jamoa", title: "Jamoamiz", note: "Har kuni mijozlar xizmatida" },
];

export function Store() {
  return (
    <section id="dokon" aria-labelledby="store-title" className="bg-ground py-24 sm:py-32">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading
          id="store-title"
          kicker="Haqiqiy do'kon"
          title="Bu — jonli savdo maskani"
          lead="Onlayn rasm emas, haqiqiy do'kon. Keling, mahsulotni qo'lingizga olib ko'ring, narxini solishtiring, savol bering — jamoamiz yordam beradi."
        />

        <div data-reveal className="mt-14 grid gap-5 md:grid-cols-3">
          {slots.map((slot) => (
            <figure key={slot.id} className="media-slot flex aspect-[4/3] flex-col justify-end p-6">
              <span
                aria-hidden="true"
                className="absolute right-4 top-4 rounded-full bg-purple px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-wider text-white"
              >
                Surat joyi
              </span>
              <svg
                aria-hidden="true"
                className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-purple-100"
                width="64"
                height="64"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.4"
              >
                <rect x="3" y="5" width="18" height="14" rx="2.5" />
                <circle cx="8.5" cy="10" r="1.6" />
                <path d="M3 17l5-4.5 3.5 3L15 12l6 5.5" />
              </svg>
              <figcaption className="relative">
                <h3 className="font-semibold text-ink">{slot.title}</h3>
                <p className="mt-1 text-sm text-ink-2">{slot.note}</p>
              </figcaption>
            </figure>
          ))}
        </div>

        <div
          data-reveal
          className="on-purple mt-6 flex flex-col items-start justify-between gap-6 rounded-[28px] bg-purple p-8 text-white sm:p-10 lg:flex-row lg:items-center"
        >
          <div className="max-w-2xl">
            <h3 className="display text-2xl sm:text-3xl">Mijozlarimiz — eng katta ishonch belgisi</h3>
            <p className="mt-3 leading-relaxed text-on-purple-2">
              O&apos;ylab topilgan sharhlar o&apos;rniga — haqiqiy mijozlarimizning hikoyalari.
              Instagram sahifamizdagi &laquo;Mijozlarimiz&raquo; bo&apos;limida har kuni yangi
              xaridlar va real lavhalar chiqib turadi.
            </p>
          </div>
          <a
            href={site.instagramUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="btn btn-yellow shrink-0"
          >
            Mijozlar hikoyalari
          </a>
        </div>
      </div>
    </section>
  );
}
