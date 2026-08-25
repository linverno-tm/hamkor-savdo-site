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
 * [VERIFIED OPENING HOURS REQUIRED] — `site.unpublished.openingHours` is null,
 * so each card says so honestly and offers the phone instead of guessing.
 * Branch 04 has no direct line yet, so it falls back to the main company number
 * rather than displaying an invented one.
 */
export function Branches() {
  const hours = site.unpublished.openingHours;

  return (
    <section id="filiallar" aria-labelledby="branches-title" className="bg-ground-2 py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading
          id="branches-title"
          kicker="Filiallar"
          title={
            <>
              Bizga{" "}
              <span className="relative z-0">
                <span className="mark">kirib o&apos;ting</span>
              </span>
            </>
          }
          lead={`${site.facts.branchCount} ta filial — Shahrixon, Asaka va Andijonda. Mahsulotni jonli ko'rib, taqqoslab tanlaysiz.`}
        />

        <ul data-reveal className="mt-14 grid gap-5 sm:grid-cols-2">
          {branches.map((b) => {
            const phone = b.phone ?? site.phone;
            const phoneLabel = b.phoneDisplay ?? site.phoneDisplay;
            return (
              <li key={b.id} className="card card-hover flex flex-col p-7 sm:p-8">
                <div className="flex items-baseline gap-4">
                  <span className="numeral text-2xl text-purple-100" aria-hidden="true">
                    {b.index}
                  </span>
                  <div>
                    <h3 className="numeral text-3xl tracking-[0.06em] text-purple">{b.city}</h3>
                    <p className="mt-1 font-semibold text-ink">{b.landmark}</p>
                  </div>
                </div>

                <dl className="mt-6 space-y-4 border-t border-line pt-5 text-sm">
                  <div className="flex gap-3">
                    <dt className="w-24 shrink-0 font-semibold uppercase tracking-wider text-ink-3">
                      Manzil
                    </dt>
                    <dd className="text-ink-2">{b.address}</dd>
                  </div>

                  <div className="flex gap-3">
                    <dt className="w-24 shrink-0 font-semibold uppercase tracking-wider text-ink-3">
                      Telefon
                    </dt>
                    <dd>
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

                  <div className="flex gap-3">
                    <dt className="w-24 shrink-0 font-semibold uppercase tracking-wider text-ink-3">
                      Ish vaqti
                    </dt>
                    <dd className="text-ink-2">
                      {hours ? hours.join(", ") : "Qo'ng'iroq orqali aniqlashtiring"}
                    </dd>
                  </div>
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
                  <Link
                    href={`/filiallar/${b.id}`}
                    className="tap self-center text-sm font-semibold text-purple underline-offset-4 hover:underline"
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
