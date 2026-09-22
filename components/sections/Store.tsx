import Link from "next/link";
import { branches } from "@/data/branches";
import { branchCover, branchPhotos } from "@/lib/photos";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { PhotoPlaceholder } from "@/components/ui/PhotoPlaceholder";

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
    const cover = branchCover(photos, b.id);
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
                     telefoni baribir kerak. Bo'sh ramka o'rniga brend bloki
                     turadi, mo'ljal esa uning ichida yoziladi. */
                  <PhotoPlaceholder className="aspect-[16/10]" caption={branch.landmark} />
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
      </div>
    </section>
  );
}
