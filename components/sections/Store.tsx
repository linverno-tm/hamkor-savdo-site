import Link from "next/link";
import { branches } from "@/data/branches";
import { branchCover, branchPhotos } from "@/lib/photos";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { PhotoPlaceholder } from "@/components/ui/PhotoPlaceholder";

/** Yoydagi suratlar soni — toq son, o'rtadagisi to'g'ri qarab turadi. */
const ARC_N = 9;

/**
 * "Haqiqiy do'kon qanday?"
 *
 * Har filialdan bitta haqiqiy surat, har biri filialning o'z sahifasiga
 * olib boradi. Hamma surat — kompaniyaning o'ziniki, stok surat yo'q.
 * Surati yo'q filial boshqa filialning suratini olmaydi — brend bloki
 * bilan qoladi.
 *
 * Ko'rinish: katta kartalar, surat kartaning ichida aylantirishga
 * ergashib siljiydi (parallaks, app/home.css > .store-img). Telefonda
 * kartalar gorizontal suriladi — keyingisi chetdan ko'rinib turadi.
 */
export function Store() {
  const cards = branches.map((b) => {
    const photos = branchPhotos(b.id, b.city);
    const cover = branchCover(photos, b.id);
    return { branch: b, cover, count: photos.length };
  });

  /* 3D yoy uchun savdo zallarining suratlari — har filialdan navbat bilan,
     jami ARC_N ta (filial sahifalaridagi suratlarning o'zi). */
  const zallar = branches.map((b) => branchPhotos(b.id, b.city).filter((p) => p.kind === "zal"));
  const arc: { src: string; alt: string }[] = [];
  for (let i = 0; arc.length < ARC_N && i < 20; i++) {
    for (const list of zallar) {
      if (list[i] && arc.length < ARC_N) arc.push({ src: list[i].srcSmall, alt: list[i].alt });
    }
  }

  return (
    <section id="dokon" aria-labelledby="store-title" className="sec store">
      <div className="wrap">
        <SectionHeading
          id="store-title"
          kicker="Haqiqiy do'kon"
          title="Bu — jonli savdo maskani"
          lead="Onlayn rasm emas, haqiqiy do'kon. Keling, mahsulotni qo'lingizga olib ko'ring, narxini solishtiring, savol bering — jamoamiz yordam beradi."
        />
      </div>

      {/* Savdo zallari 3D yoy bo'ylab (ichkariga egilgan, o'rtadagi surat
          to'g'ri qaraydi), aylantirilganda sekin buriladi — sof CSS
          (app/home-sections.css > .arc). Bezak: har surat filial
          sahifasidagi galereyada ham bor. */}
      {arc.length >= 5 ? (
        <div className="arc" aria-hidden="true">
          <div className="arc-ring">
            {arc.map((p, i) => (
              <span key={p.src} className="arc-card" style={{ ["--a" as string]: i - (arc.length - 1) / 2 }}>
                <img src={p.src} alt="" width={480} height={360} loading="lazy" decoding="async" />
              </span>
            ))}
          </div>
        </div>
      ) : null}

      <ul data-m="" data-m-stagger className="store-rail">
        {cards.map(({ branch, cover, count }) => (
          <li key={branch.id} className="store-item">
            <Link href={`/filiallar/${branch.id}`} className="store-card group">
              <span className="store-media">
                {cover ? (
                  <img
                    src={cover.src}
                    srcSet={`${cover.srcSmall} 480w, ${cover.src} 960w`}
                    sizes="(max-width: 640px) 85vw, 50vw"
                    width={960}
                    height={720}
                    alt={cover.alt}
                    loading="lazy"
                    decoding="async"
                    className="store-img"
                  />
                ) : (
                  <PhotoPlaceholder className="h-full" caption={branch.landmark} />
                )}
              </span>
              <span className="store-body">
                <span>
                  <span className="store-city numeral">{branch.city}</span>
                  <span className="store-land">{branch.landmark}</span>
                </span>
                <span className="store-more">
                  {count > 0 ? `${count} ta surat` : "Batafsil"} &rarr;
                </span>
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </section>
  );
}
