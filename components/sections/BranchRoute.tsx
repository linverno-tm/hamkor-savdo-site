import Link from "next/link";
import { branches } from "@/data/branches";
import { CITY_RU } from "@/components/layout/RuChrome";

/**
 * Filiallar sxemasi (/filiallar/) — metro xaritasi uslubida: 4 filial haqiqiy
 * koordinatasi bo'yicha, ular orasidagi yo'l ekranga kirganda chiziladi,
 * nuqtalar navbat bilan "tushadi" (app/pages.css > .rt). Har nuqta — filial
 * sahifasiga havola; ustiga kelinsa pastdagi o'sha filial kartasi ham yonadi.
 *
 * Bosh sahifadagi Yandex xaritani takrorlamaydi: bu yerda faqat "qayerda va bir-biriga
 * qanchalik yaqin" — tashqi xizmatsiz, yengil SVG.
 *
 * Yo'l chizilgach yetkazish mashinasi g'arbdan sharqqa bir marta (~4 s) yuradi
 * (egasi 27.10: "so'z emas, grafika gapirsin"). Mashina — Sketchfab'dagi CC BY
 * 3D model, render qilingan rasm (WebGL yo'q, 4 KB); muallif pastda. JS'siz
 * yoki kamaytirilgan harakatda mashina boshlang'ich nuqtada turadi.
 *
 * `light` — och fonli bo'lim uchun (yo'nalish sahifalari), `lang="ru"` —
 * shahar nomlari ruscha, havolalar oddiy <a> (TopicPage > LangLink kabi).
 */
const W = 760;
const H = 360;
/* Chetlar: chapda va o'ngda yozuvlar uchun joy (g'arbdagi yozuv chapga, sharqdagisi o'ngga). */
const M = { l: 230, r: 200, t: 70, b: 80 };
/** Yo'l g'arbdan sharqqa — avtomobil yo'li tartibida. */
const ORDER = ["Shahrixon", "Asaka", "Andijon"];

const T = {
  uz: { label: "Filiallar joylashuvi", credit: "3D model" },
  ru: { label: "Расположение магазинов", credit: "3D-модель" },
};

export function BranchRoute({ lang = "uz", light = false }: { lang?: "uz" | "ru"; light?: boolean }) {
  const t = T[lang];
  const lat0 = Math.min(...branches.map((b) => b.lat));
  const lat1 = Math.max(...branches.map((b) => b.lat));
  const lng0 = Math.min(...branches.map((b) => b.lng));
  const lng1 = Math.max(...branches.map((b) => b.lng));
  const kx = Math.cos(((lat0 + lat1) / 2) * (Math.PI / 180)); // uzunlik darajasi shimolda qisqaradi
  const s = Math.min((W - M.l - M.r) / ((lng1 - lng0) * kx || 1), (H - M.t - M.b) / (lat1 - lat0 || 1));
  const ox = M.l + (W - M.l - M.r - (lng1 - lng0) * kx * s) / 2;
  const oy = M.t + (H - M.t - M.b - (lat1 - lat0) * s) / 2;

  /* Bir shahardagi filiallar (Shahrixon — 400 m) bitta nuqtaga tushmasin: ikkinchisi pastroqda. */
  const seen = new Map<string, number>();
  const pts = branches.map((b) => {
    const n = seen.get(b.city) ?? 0;
    seen.set(b.city, n + 1);
    const x = ox + (b.lng - lng0) * kx * s;
    return { b, x, y: oy + (lat1 - b.lat) * s + n * 44, left: x < W / 2, n };
  });

  const route = ORDER.map((c) => pts.find((p) => p.b.city === c && p.n === 0)).filter((p): p is (typeof pts)[number] => !!p);
  const d = route
    .map((p, i) => {
      if (i === 0) return `M ${p.x.toFixed(1)} ${p.y.toFixed(1)}`;
      const q = route[i - 1];
      const mx = (q.x + p.x) / 2;
      return `C ${mx.toFixed(1)} ${q.y.toFixed(1)}, ${mx.toFixed(1)} ${p.y.toFixed(1)}, ${p.x.toFixed(1)} ${p.y.toFixed(1)}`;
    })
    .join(" ");
  /* Mashina yo'li: o'sha egri chiziq, boshlanishi (0,0) ga ko'chirilgan — <g> boshlang'ich nuqtada turadi. */
  const start = route[0];
  const rel = d.replace(/(-?\d+(?:\.\d+)?) (-?\d+(?:\.\d+)?)/g, (_, x: string, y: string) => `${(+x - start.x).toFixed(1)} ${(+y - start.y).toFixed(1)}`);

  return (
    <figure data-m="" data-drive="" className={light ? "rt rt--light" : "rt"} aria-label={t.label}>
      <svg viewBox={`0 0 ${W} ${H}`} className="rt-svg" role="presentation">
        <defs>
          <pattern id="rt-dots" width="22" height="22" patternUnits="userSpaceOnUse">
            <circle cx="2" cy="2" r="1.3" />
          </pattern>
        </defs>
        <rect className="rt-grid" width={W} height={H} fill="url(#rt-dots)" />
        <path className="rt-road" d={d} pathLength={1} />
        <path className="rt-road rt-road--glow" d={d} pathLength={1} />
        {pts.map(({ b, x, y, left, n }, i) => (
          <Pin
            key={b.id}
            ru={lang === "ru"}
            href={`/filiallar/${b.id}`}
            className={n > 0 ? "rt-pin rt-pin--dup" : "rt-pin"}
            data-branch={b.id}
            style={{ ["--i" as string]: i }}
          >
            <circle className="rt-pulse" cx={x} cy={y} r="13" />
            <circle className="rt-dot" cx={x} cy={y} r="13" />
            <text className="rt-idx" x={x} y={y + 4.5} textAnchor="middle">
              {b.index}
            </text>
            <text className="rt-city" x={left ? x - 24 : x + 24} y={y - 2} textAnchor={left ? "end" : "start"}>
              {lang === "ru" ? CITY_RU[b.city] ?? b.city : b.city}
            </text>
            <text className="rt-land" x={left ? x - 24 : x + 24} y={y + 16} textAnchor={left ? "end" : "start"}>
              {b.landmark}
            </text>
          </Pin>
        ))}
        {/* Rasm chapga qaragan — g'arbdan sharqqa yurgani uchun teskari. */}
        {start ? (
          <g className="rt-truck" transform={`translate(${start.x.toFixed(1)} ${start.y.toFixed(1)})`} aria-hidden="true">
            <image href="/3d/yetkazish-160.webp" x="-36" y="-62" width="72" height="47" transform="scale(-1 1)" />
            <animateMotion dur="4s" begin="indefinite" fill="freeze" calcMode="spline" keyTimes="0;1" keySplines=".45 0 .25 1" path={rel} />
          </g>
        ) : null}
      </svg>
      <figcaption className="rt-credit" translate="no">
        {t.credit}:{" "}
        <a href="https://sketchfab.com/3d-models/delivery-truck-6842eac23e6d4484bad081522b1602d8" target="_blank" rel="noopener noreferrer">
          Delivery Truck
        </a>{" "}
        —{" "}
        <a href="https://sketchfab.com/farooq.smurf" target="_blank" rel="noopener noreferrer">
          farooq.smurf
        </a>{" "}
        (CC BY 4.0)
      </figcaption>
    </figure>
  );
}

/** Ruscha sahifada oddiy <a>: o'zbekcha filial sahifasiga to'liq yuklanib o'tilsin (TopicPage > LangLink). */
function Pin({ ru, href, ...rest }: { ru: boolean; href: string } & React.ComponentProps<"a">) {
  return ru ? <a href={href} {...rest} /> : <Link href={href} {...rest} />;
}
