import Link from "next/link";
import { site } from "@/data/site";
import { branches } from "@/data/branches";
import { branchPhotos } from "@/lib/photos";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Logo } from "@/components/ui/Logo";

/**
 * Scene 08 — "Haqiqiy do'kon qanday?"
 *
 * One real photograph per branch, each linking to that branch's own page where
 * the full set lives. Every image is the company's own — no stock photography
 * stands in for the business.
 *
 * A branch that has not supplied photos yet keeps a clearly-labelled empty
 * frame rather than borrowing another branch's picture: the section is meant to
 * prove these places are real, and a stand-in would quietly undo that.
 */
export function Store() {
  const cards = branches.map((b) => {
    const photos = branchPhotos(b.id, b.city);
    // Prefer the storefront shot; fall back to the first interior.
    const cover = photos.find((p) => p.kind === "tashqi") ?? photos[0] ?? null;
    return { branch: b, cover, count: photos.length };
  });

  return (
    <section id="dokon" aria-labelledby="store-title" className="bg-ground py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading
          id="store-title"
          kicker="Haqiqiy do'kon"
          title="Bu — jonli savdo maskani"
          lead="Onlayn rasm emas, haqiqiy do'kon. Keling, mahsulotni qo'lingizga olib ko'ring, narxini solishtiring, savol bering — jamoamiz yordam beradi."
        />

        {/* Ikki ustun, to'rtta emas: filial surati kartochkaning bosh qismiga
            aylanadi. To'rtta kichkina rasm yonma-yon turganda hech qaysisi
            ko'rinmaydi. */}
        <ul data-reveal className="mt-10 grid gap-5 sm:grid-cols-2">
          {cards.map(({ branch, cover, count }) => (
            <li key={branch.id}>
              <Link
                href={`/filiallar/${branch.id}`}
                className="card card-hover group flex h-full flex-col overflow-hidden"
              >
                {cover ? (
                  <img
                    src={cover.src}
                    srcSet={`${cover.srcSmall} 480w, ${cover.src} 960w`}
                    sizes="(max-width: 640px) 100vw, 50vw"
                    width={960}
                    height={720}
                    alt={cover.alt}
                    loading="lazy"
                    decoding="async"
                    className="block aspect-[16/10] w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                  />
                ) : (
                  /* Suratsiz filial ham kartochkasini yo'qotmaydi — manzili va
                     telefoni baribir kerak. Bo'sh ramka o'rniga brend belgisi
                     turadi: sahifa buzilgandek emas, tayyorlanayotgandek
                     ko'rinsin. */
                  <span className="media-slot flex aspect-[16/10] w-full flex-col items-center justify-center gap-3 rounded-none border-0 border-b border-dashed text-center text-sm text-ink-3">
                    <Logo variant="mark" className="h-10 w-auto text-purple-100" />
                    Suratlar tayyorlanmoqda
                  </span>
                )}
                <span className="flex flex-1 flex-col p-5">
                  <span className="numeral text-2xl tracking-[0.06em] text-purple">
                    {branch.city}
                  </span>
                  <span className="mt-1 text-sm text-ink-2">{branch.landmark}</span>
                  <span className="mt-3 text-sm font-semibold text-purple group-hover:underline">
                    {count > 0 ? `${count} ta surat` : "Batafsil"} &rarr;
                  </span>
                </span>
              </Link>
            </li>
          ))}
        </ul>

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
