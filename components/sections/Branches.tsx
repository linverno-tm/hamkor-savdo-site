import Link from "next/link";
import { branches, mapsUrl } from "@/data/branches";
import { site } from "@/data/site";
import { SectionHeading } from "@/components/ui/SectionHeading";

/**
 * Scene 07 — "Qayerga boraman?"
 *
 * The branch finder. Every address, phone and map link is plain text and a real
 * link, readable with JavaScript and animation switched off entirely.
 *
 * Ish vaqti (2026-09-17, egasi): Andijon 9:00–22:00, qolgan uch filial 8:00–18:00
 * — har biri `data/branches.ts`da o'zining `hours` maydonida.
 */
export function Branches() {
  return (
    <section id="filiallar" aria-labelledby="branches-title" className="bg-ground-2 py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading
          id="branches-title"
          kicker="Filiallar"
          title={
            <>
              Bizga kirib o&apos;ting
            </>
          }
          lead={`${site.facts.branchCount} ta filial — Shahrixon, Asaka va Andijonda. Mahsulotni jonli ko'rib, taqqoslab tanlaysiz.`}
        />

        {/* Yetkazib berish endi butun mamlakat bo'ylab, filiallar esa faqat
            Andijon viloyatida. Boshqa viloyatdagi odam bu bo'limga kelib
            "menga to'g'ri kelmas ekan" deb chiqib ketmasin — xarid uchun
            filialga kelish shart emasligi shu yerda aytiladi. */}
        <p data-reveal className="mt-6 max-w-2xl text-lg leading-relaxed text-ink-2">
          Boshqa viloyatdamisiz?{" "}
          <strong className="font-semibold text-ink">Filialga kelish shart emas</strong> —
          mahsulotni telefon orqali tanlab, rasmiylashtirasiz va{" "}
          {site.deliveryArea} yetkazib beramiz.
        </p>

        <ul data-reveal className="mt-14 grid gap-5 lg:grid-cols-2">
          {branches.map((b) => {
            const phone = b.phone ?? site.phone;
            const phoneLabel = b.phoneDisplay ?? site.phoneDisplay;
            return (
              <li
                key={b.id}
                className="card card-hover stretch-host group flex cursor-pointer flex-col p-7 sm:p-8"
              >
                <div className="flex items-baseline gap-4">
                  <span className="numeral text-2xl text-purple-100" aria-hidden="true">
                    {b.index}
                  </span>
                  <div>
                    <h3 className="numeral text-3xl tracking-[0.06em] text-purple">{b.city}</h3>
                    <p className="mt-1 font-semibold text-ink">{b.landmark}</p>
                  </div>
                </div>

                <dl className="mt-6 grid grid-cols-2 gap-x-4 gap-y-4 border-t border-line pt-5 text-sm sm:grid-cols-[repeat(auto-fit,minmax(9rem,1fr))]">
                  <div className="col-span-2 sm:col-span-1">
                    <dt className="font-semibold uppercase tracking-wider text-ink-3">Manzil</dt>
                    <dd className="mt-1 text-ink-2">{b.address}</dd>
                  </div>

                  <div>
                    <dt className="font-semibold uppercase tracking-wider text-ink-3">Telefon</dt>
                    <dd className="mt-1">
                      <a
                        href={`tel:${phone}`}
                        className="font-semibold text-purple underline-offset-4 hover:underline"
                      >
                        {phoneLabel}
                      </a>
                      {b.phone ? null : (
                        <span className="mt-0.5 block text-xs text-ink-3">
                          Bu filial uchun umumiy raqam
                        </span>
                      )}
                    </dd>
                  </div>

                  <div>
                    <dt className="font-semibold uppercase tracking-wider text-ink-3">Ish vaqti</dt>
                    <dd className="mt-1 text-ink-2">{b.hours}</dd>
                  </div>

                  {b.instagram ? (
                    <div>
                      <dt className="font-semibold uppercase tracking-wider text-ink-3">
                        Instagram
                      </dt>
                      <dd className="mt-1">
                        <a
                          href={b.instagramUrl!}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="font-semibold text-purple underline-offset-4 hover:underline"
                        >
                          @{b.instagram}
                        </a>
                      </dd>
                    </div>
                  ) : null}
                </dl>

                <div className="mt-auto flex flex-wrap gap-3 pt-7">
                  <a
                    href={mapsUrl(b)}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn btn-primary !min-h-11 !px-5 text-sm"
                  >
                    Xaritada ochish
                  </a>
                  <a href={`tel:${phone}`} className="btn btn-outline !min-h-11 !px-5 text-sm">
                    Qo&apos;ng&apos;iroq qilish
                  </a>
                  {/* `stretch-link` bu havolaning bosiladigan maydonini butun
                      kartochkaga yoyadi (globals.css). Kartochkaning bo'sh
                      joyiga bosgan odam ham filial sahifasiga o'tadi. */}
                  <Link
                    href={`/filiallar/${b.id}`}
                    aria-label={`${b.city} — ${b.landmark} filiali haqida batafsil`}
                    className="stretch-link tap self-center text-sm font-semibold text-purple underline-offset-4 group-hover:underline hover:underline"
                  >
                    Batafsil &rarr;
                  </Link>
                </div>
              </li>
            );
          })}
        </ul>
      </div>
    </section>
  );
}
