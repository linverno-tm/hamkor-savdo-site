import Link from "next/link";
import { branches, mapsUrl } from "@/data/branches";
import { site } from "@/data/site";
import { branchCover, branchPhotos } from "@/lib/photos";
import { OpenStatus } from "@/components/ui/OpenStatus";
import { CITY_RU } from "@/components/layout/RuChrome";
import { Yukl } from "@/components/ui/Yukl";
import { lqipStyle } from "@/lib/lqip";

/**
 * Filiallar — xarita birinchi.
 *
 * Xarita — o'zimiz chizgan SVG sxema: nuqtalar filiallarning haqiqiy
 * koordinatalaridan (content.json, admin panel) proyeksiya qilinadi, bir
 * shahardagi filiallar bitta belgida "2 ta" bo'lib qo'shiladi. Yandex
 * xaritasining ~1 MB skripti bosh sahifada yuklanmaydi; har kartada esa
 * aniq nuqtaga Google Maps havolasi bor (filial sahifasida to'liq xarita).
 *
 * Tepada — Shahrixon (Ozodbek savdo markazi) filialining jonli videosi:
 * faqat ekranga kirganda, harakatga ruxsat va trafik tejash o'chiq bo'lsa
 * yuklanadi (lib/motion.ts > data-lazy-video). Aks holda — poster.
 */

/* Xarita chegarasi (Andijon viloyatining filiallar joylashgan qismi). */
const BOX = { w: 640, h: 360, lng0: 71.98, lng1: 72.42, lat0: 40.6, lat1: 40.8 };
const px = (lng: number) => Math.round(((lng - BOX.lng0) / (BOX.lng1 - BOX.lng0)) * BOX.w);
const py = (lat: number) => Math.round(((BOX.lat1 - lat) / (BOX.lat1 - BOX.lat0)) * BOX.h);

function haversine(lat1: number, lng1: number, lat2: number, lng2: number): number {
  const r = (d: number) => (d * Math.PI) / 180;
  const a = Math.sin(r(lat2 - lat1) / 2) ** 2 + Math.cos(r(lat1)) * Math.cos(r(lat2)) * Math.sin(r(lng2 - lng1) / 2) ** 2;
  return 6371 * 2 * Math.asin(Math.sqrt(a));
}

const T = {
  uz: {
    count: (n: number) => `${n} ta`,
    caption: `${site.freeDeliveryArea} bo'ylab yetkazish va o'rnatish bepul · masofalar to'g'ri chiziqda`,
    mapLabel: "Filiallar xaritasi",
    video: "Shahrixon filiali, Ozodbek savdo markazi — tashqi ko'rinish",
    videoCap: "Shahrixon, Ozodbek savdo markazi",
    hours: (h: string) => `Har kuni ${h}`,
    closed: "Vaqtincha yopiq",
    map: "Xaritada",
  },
  ru: {
    count: (n: number) => `${n}`,
    caption: "Доставка и установка по Андижанской области бесплатно · расстояния по прямой",
    mapLabel: "Карта магазинов",
    video: "Магазин в Шахрихане, торговый центр Озодбек — снаружи",
    videoCap: "Шахрихан, торговый центр Озодбек",
    hours: (h: string) => `Часы работы: ${h}`,
    closed: "Временно закрыт",
    map: "На карте",
  },
};

/**
 * @param title — sahifaning o'z sarlavhasi (yo'nalish/ruscha sahifalarda
 *   avvalgi "Filiallarimiz" / "Наши магазины" matni saqlanadi).
 */
export function UyFiliallar({ lang = "uz", title }: { lang?: "uz" | "ru"; title?: string } = {}) {
  const tr = T[lang];
  const cityName = (c: string) => (lang === "ru" ? CITY_RU[c] ?? c : c);
  /* Shahar bo'yicha guruh: o'rtacha nuqta va soni. */
  const cities = new Map<string, { n: number; lat: number; lng: number }>();
  for (const b of branches) {
    const c = cities.get(b.city) ?? { n: 0, lat: 0, lng: 0 };
    c.n += 1;
    c.lat += b.lat;
    c.lng += b.lng;
    cities.set(b.city, c);
  }
  const pins = Array.from(cities, ([city, c]) => ({
    city,
    n: c.n,
    x: px(c.lng / c.n),
    y: py(c.lat / c.n),
  })).sort((a, b) => a.x - b.x);
  const route = pins.map((p, i) => `${i ? "L" : "M"}${p.x} ${p.y}`).join(" ");
  /* Shaharlar orasidagi to'g'ri chiziq masofasi (km) — koordinatalardan hisoblanadi. */
  const groups = Array.from(cities.values()).map((c) => ({ lat: c.lat / c.n, lng: c.lng / c.n }));
  const byX = [...groups].sort((a, b) => a.lng - b.lng);
  const legs = byX.slice(1).map((b, i) => {
    const a = byX[i];
    const km = Math.round(haversine(a.lat, a.lng, b.lat, b.lng));
    return { km, x: (px(a.lng) + px(b.lng)) / 2, y: (py(a.lat) + py(b.lat)) / 2 };
  });

  return (
    <section id="filiallar" aria-labelledby="fil-title" className="uy-sec uy-tun">
      <div className="u-wrap">
        <h2 id="fil-title" className="u-h2">
          {title ?? `${branches.length} ta do'kon — ${site.freeDeliveryArea}da`}
        </h2>

        <div className="fil-top">
          <figure className="fil-map" data-m="">
            <svg viewBox={`0 0 ${BOX.w} ${BOX.h}`} role="img" aria-label={`${tr.mapLabel}: ${pins.map((p) => cityName(p.city)).join(", ")}`}>
              <defs>
                <pattern id="fil-grid" width="32" height="32" patternUnits="userSpaceOnUse">
                  <path d="M32 0H0V32" fill="none" />
                </pattern>
              </defs>
              <rect className="fil-grid" width={BOX.w} height={BOX.h} fill="url(#fil-grid)" />
              {/* Bepul yetkazish hududi — yumshoq sariq dog'. */}
              <ellipse className="fil-zone" cx={BOX.w * 0.55} cy={BOX.h * 0.5} rx={BOX.w * 0.47} ry={BOX.h * 0.44} />
              <path className="fil-route" d={route} pathLength={1} />
              {legs.map((l) => (
                <text key={`${l.x}-${l.y}`} className="fil-km" x={l.x} y={l.y - 12} textAnchor="middle">
                  ≈{l.km} {lang === "ru" ? "км" : "km"}
                </text>
              ))}
              {pins.map((p, i) => (
                <g key={p.city} className="fil-pin" transform={`translate(${p.x} ${p.y})`}>
                  <circle className="fil-pulse" r="18" />
                  <path className="fil-house" d="M-11 10V-2L0 -11l11 9v12Z" />
                  {/* Chetki shaharlar nomi tepada va ichkariga qarab — xaritadan chiqmasin
                      va masofa yozuvi bilan ustma-ust tushmasin (kirillda so'z uzunroq). */}
                  <text
                    className="fil-city"
                    x={i === 0 ? -14 : i === pins.length - 1 ? 14 : 0}
                    y={i === 0 ? -24 : 34}
                    textAnchor={i === 0 ? "start" : i === pins.length - 1 ? "end" : "middle"}
                  >
                    {cityName(p.city)}
                    {p.n > 1 ? ` · ${tr.count(p.n)}` : ""}
                  </text>
                </g>
              ))}
            </svg>
            <figcaption>{tr.caption}</figcaption>
          </figure>

          <figure className="fil-video yk" style={lqipStyle("ozodbek")}>
            {/* Poster — oddiy lazy <img>: video `poster` atributi sahifa ochilishi bilan
                yuklanib, hero rasmi bilan tarmoq uchun bellashardi (LH 01.11).
                Muqova va video kelguncha — yuklanish qatlami (foizli chiziq). */}
            <img
              src="/uy/ozodbek-poster-640.webp"
              srcSet="/uy/ozodbek-poster-640.webp 640w, /uy/ozodbek-poster-1280.webp 1280w"
              sizes="(min-width: 1024px) 40vw, 92vw"
              width={640}
              height={360}
              alt=""
              loading="lazy"
              decoding="async"
            />
            <Yukl />
            <video
              data-lazy-video="/uy/ozodbek-1280.mp4"
              data-lazy-webm="/uy/ozodbek-1280-av1.webm"
              muted
              loop
              playsInline
              preload="none"
              width={1280}
              height={720}
              aria-label={tr.video}
            />
            <figcaption>{tr.videoCap}</figcaption>
          </figure>
        </div>

        <ul className="fil-list">
          {branches.map((b) => {
            const cover = branchCover(branchPhotos(b.id, b.city), b.id);
            const phone = b.phone ?? site.phone;
            const phoneDisplay = b.phoneDisplay ?? site.phoneDisplay;
            return (
              <li key={b.id} className="fil-i">
                {cover ? (
                  <span className="fil-img yk">
                    <Yukl />
                    <img src={cover.srcSmall} width={480} height={360} alt={cover.alt} loading="lazy" decoding="async" />
                  </span>
                ) : null}
                <div className="fil-body">
                  <h3 className="fil-name">
                    {lang === "ru" ? (
                      <a href={`/filiallar/${b.id}/`} hrefLang="uz">
                        {cityName(b.city)}
                        <span>{b.landmark}</span>
                      </a>
                    ) : (
                      <Link href={`/filiallar/${b.id}`}>
                        {b.city}
                        <span>{b.landmark}</span>
                      </Link>
                    )}
                  </h3>
                  <p className="fil-hours">
                    {b.closed ? (lang === "ru" ? tr.closed : b.closedNote || tr.closed) : tr.hours(b.hours)}{" "}
                    <OpenStatus hours={b.hours} closed={b.closed} />
                  </p>
                  <div className="fil-act">
                    <a href={`tel:${phone}`} className="fil-tel">
                      {phoneDisplay}
                    </a>
                    <a href={mapsUrl(b)} target="_blank" rel="noopener noreferrer" className="fil-map-a">
                      {tr.map}
                    </a>
                  </div>
                </div>
              </li>
            );
          })}
        </ul>
      </div>
    </section>
  );
}
