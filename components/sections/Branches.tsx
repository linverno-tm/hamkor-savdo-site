import Link from "next/link";
import { branches, cityList, mapsUrl } from "@/data/branches";
import { site } from "@/data/site";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { BranchMap, type MapBranch } from "@/components/sections/BranchMap";

/**
 * "Bizga eng yaqin Hamkor" — chapda filiallar ro'yxati, o'ngda xarita.
 *
 * Ro'yxatdagi har bir manzil, telefon va havola oddiy matn va haqiqiy havola:
 * JavaScript'siz ham to'liq ishlaydi. "Xaritada" havolasi JS bo'lsa xaritani
 * shu filialga uchiradi (BranchMap), bo'lmasa tashqi xaritani ochadi.
 *
 * Ish vaqti har bir filialning `hours` maydonida (data/branches.ts).
 */
export function Branches() {
  const mapBranches: MapBranch[] = branches.map((b) => ({
    id: b.id,
    index: b.index,
    title: `${b.city} — ${b.landmark}`,
    address: b.address,
    hours: b.hours,
    lat: b.lat,
    lng: b.lng,
    href: `/filiallar/${b.id}/`,
  }));

  return (
    <section id="filiallar" aria-labelledby="branches-title" className="scroll-mt-20 bg-ground py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <SectionHeading
          id="branches-title"
          kicker="Filiallar"
          title="Bizga eng yaqin Hamkor"
          lead={`${site.facts.branchCount} ta filial — ${cityList({ counts: false })}da. Boshqa viloyatdamisiz? Filialga kelish shart emas — telefon orqali tanlab, ${site.deliveryArea} yetkazib beramiz.`}
        />

        <div data-reveal className="mt-10 grid gap-5 lg:grid-cols-[0.9fr_1.1fr] lg:gap-6">
          <ul className="grid gap-3">
            {branches.map((b) => {
              const phone = b.phone ?? site.phone;
              const phoneLabel = b.phoneDisplay ?? site.phoneDisplay;
              return (
                <li
                  key={b.id}
                  data-branch-item={b.id}
                  className="branch-item rounded-[18px] border border-line bg-ground p-5 transition-colors"
                >
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <h3 className="display text-xl text-ink">{b.city}</h3>
                      <p className="mt-0.5 text-sm font-medium text-purple">{b.landmark}</p>
                    </div>
                    <span className="shrink-0 rounded-full bg-ground-2 px-3 py-1 text-xs font-semibold text-ink-2">
                      {b.hours}
                    </span>
                  </div>

                  {b.closed ? (
                    <p className="mt-3 rounded-[10px] border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                      <strong className="font-semibold">Vaqtincha yopiq.</strong>
                      {b.closedNote ? ` ${b.closedNote}` : null}
                    </p>
                  ) : null}

                  <p className="mt-3 text-sm leading-relaxed text-ink-2">{b.address}</p>

                  <div className="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold">
                    <a href={`tel:${phone}`} className="text-purple underline-offset-4 hover:underline">
                      {phoneLabel}
                    </a>
                    <a
                      href={mapsUrl(b)}
                      target="_blank"
                      rel="noopener noreferrer"
                      data-map-focus={b.id}
                      className="text-ink-2 underline-offset-4 hover:text-purple hover:underline"
                    >
                      Xaritada
                    </a>
                    <Link
                      href={`/filiallar/${b.id}`}
                      aria-label={`${b.city} — ${b.landmark} filiali haqida batafsil`}
                      className="text-ink-2 underline-offset-4 hover:text-purple hover:underline"
                    >
                      Batafsil &rarr;
                    </Link>
                  </div>
                </li>
              );
            })}
          </ul>

          <div className="lg:sticky lg:top-24 lg:self-start">
            <BranchMap branches={mapBranches} apiKey={site.yandexMapsKey} />
          </div>
        </div>
      </div>
    </section>
  );
}
