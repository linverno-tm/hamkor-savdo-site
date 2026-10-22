import Link from "next/link";
import { branches, cityList, mapsUrl } from "@/data/branches";
import { site } from "@/data/site";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { BranchMap, type MapBranch } from "@/components/sections/BranchMap";
import { OpenStatus } from "@/components/ui/OpenStatus";

/**
 * "Bizga eng yaqin Hamkor" — chapda filiallar ro'yxati, o'ngda xarita.
 *
 * Ro'yxatdagi har bir manzil, telefon va havola oddiy matn va haqiqiy havola:
 * JavaScript'siz ham to'liq ishlaydi. "Xaritada" havolasi JS bo'lsa xaritani
 * shu filialga uchiradi (BranchMap), bo'lmasa tashqi xaritani ochadi.
 * Tanlangan filial kartasi binafsha bo'ladi (`data-active`, BranchMap).
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
    phone: b.phone ?? site.phone,
    phoneDisplay: b.phoneDisplay ?? site.phoneDisplay,
  }));

  return (
    <section id="filiallar" aria-labelledby="branches-title" className="sec branches scroll-mt-20">
      <div className="wrap">
        <SectionHeading
          id="branches-title"
          kicker="Filiallar"
          title="Bizga eng yaqin Hamkor"
          lead={`${site.facts.branchCount} ta filial — ${cityList({ counts: false })}da. Boshqa viloyatdamisiz? Filialga kelish shart emas — telefon orqali tanlab, ${site.deliveryArea} yetkazib beramiz.`}
        />

        <div className="branches-grid">
          <ul data-m="" data-m-stagger className="branch-list">
            {branches.map((b) => {
              const phone = b.phone ?? site.phone;
              const phoneLabel = b.phoneDisplay ?? site.phoneDisplay;
              return (
                <li key={b.id} data-branch-item={b.id} className="branch-item">
                  <div className="branch-top">
                    <span className="branch-idx numeral" aria-hidden="true">
                      {b.index}
                    </span>
                    <div className="min-w-0 flex-1">
                      <h3 className="branch-city">{b.city}</h3>
                      <p className="branch-land">{b.landmark}</p>
                      <OpenStatus hours={b.hours} closed={b.closed} />
                    </div>
                    <span className="branch-hours">{b.hours}</span>
                  </div>

                  {b.closed ? (
                    <p className="mt-3 rounded-[10px] border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                      <strong className="font-semibold">Vaqtincha yopiq.</strong>
                      {b.closedNote ? ` ${b.closedNote}` : null}
                    </p>
                  ) : null}

                  <p className="branch-addr">{b.address}</p>

                  <div className="branch-actions">
                    <a href={`tel:${phone}`} className="branch-call">
                      {phoneLabel}
                    </a>
                    <a
                      href={mapsUrl(b)}
                      target="_blank"
                      rel="noopener noreferrer"
                      data-map-focus={b.id}
                      className="branch-link"
                    >
                      Xaritada
                    </a>
                    <Link
                      href={`/filiallar/${b.id}`}
                      aria-label={`${b.city} — ${b.landmark} filiali haqida batafsil`}
                      className="branch-link"
                    >
                      Batafsil &rarr;
                    </Link>
                  </div>
                </li>
              );
            })}
          </ul>

          <div data-m="zoom" className="branch-map lg:sticky lg:top-24 lg:self-start">
            <BranchMap branches={mapBranches} apiKey={site.yandexMapsKey} />
          </div>
        </div>
      </div>
    </section>
  );
}
