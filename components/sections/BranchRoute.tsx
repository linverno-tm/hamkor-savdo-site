import Link from "next/link";
import { branches } from "@/data/branches";

/**
 * Filiallar sxemasi (/filiallar/) — metro xaritasi uslubida: 4 filial haqiqiy
 * koordinatasi bo'yicha, ular orasidagi yo'l ekranga kirganda chiziladi,
 * nuqtalar navbat bilan "tushadi" (app/pages.css > .rt). Har nuqta — filial
 * sahifasiga havola; ustiga kelinsa pastdagi o'sha filial kartasi ham yonadi.
 *
 * Bosh sahifadagi Yandex xaritani takrorlamaydi: bu yerda faqat "qayerda va bir-biriga
 * qanchalik yaqin" — tashqi xizmatsiz, yengil SVG.
 */
const W = 760;
const H = 360;
/* Chetlar: chapda va o'ngda yozuvlar uchun joy (g'arbdagi yozuv chapga, sharqdagisi o'ngga). */
const M = { l: 230, r: 200, t: 70, b: 80 };
/** Yo'l g'arbdan sharqqa — avtomobil yo'li tartibida. */
const ORDER = ["Shahrixon", "Asaka", "Andijon"];

export function BranchRoute() {
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

  return (
    <figure data-m="" className="rt" aria-label="Filiallar joylashuvi">
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
          <Link
            key={b.id}
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
              {b.city}
            </text>
            <text className="rt-land" x={left ? x - 24 : x + 24} y={y + 16} textAnchor={left ? "end" : "start"}>
              {b.landmark}
            </text>
          </Link>
        ))}
      </svg>
    </figure>
  );
}
