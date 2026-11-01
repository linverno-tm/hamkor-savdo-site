import Link from "next/link";
import { site } from "@/data/site";

/**
 * "Do'kondan uyingizgacha" — muddatli to'lov hikoyasi, so'z emas, rasm bilan.
 *
 * 1) Yo'l: do'kon → hujjat → uy. Kompyuterda yo'l gorizontal, telefonda
 *    tik. Yuk mashinasi sahifa aylantirilganda yo'l bo'ylab yuradi
 *    (CSS scroll-driven animation; qo'llamaydigan brauzerda — uy oldida turadi).
 * 2) Taqvim: mahsulot "bugun" uyda (yongan uy), to'lov esa oylarga bo'lingan —
 *    ekranga kirganda derazalar birin-ketin yonadi (lib/motion.ts > data-m).
 *    Summa yoki foiz ko'rsatilmaydi: oylik to'lov mahsulot va muddatga bog'liq
 *    (egasi: kalkulyator qo'yilmaydi).
 *
 * `id="muddatli-tolov"` — filial sahifalari `/#muddatli-tolov` ga havola beradi.
 */
export function UyYol() {
  const oy = site.facts.installmentMonthsMax;
  const months = Array.from({ length: oy }, (_, i) => i + 1);

  return (
    <section id="muddatli-tolov" aria-labelledby="yol-title" className="uy-sec uy">
      <div className="u-wrap">
        <h2 id="yol-title" className="u-h2">
          Do&apos;kondan uyingizgacha — uch qadam
        </h2>

        <div className="yol" data-m="">
        {/* Yo'l va yuk mashinasi — bezak. */}
        <div className="yol-road" aria-hidden="true">
          <span className="yol-truck">
            <svg viewBox="0 0 64 36">
              <path className="t-kuzov" d="M2 4h36v24H2z" />
              <path className="t-kabina" d="M38 12h13l9 9v7H38z" />
              <path className="t-oyna" d="M42 15h8l5 6H42z" />
              <circle className="t-g" cx="14" cy="29" r="5" />
              <circle className="t-g" cx="49" cy="29" r="5" />
            </svg>
          </span>
        </div>
          <ol className="yol-list">
          <li className="yol-step">
            <div className="yol-pic yol-pic--foto">
              <img
                src="/filiallar/shahrixon-ozodbek/zal-7-480.webp"
                width={480}
                height={360}
                alt="HAMKOR SAVDO Shahrixon filiali savdo zali"
                loading="lazy"
                decoding="async"
              />
            </div>
            <p className="yol-n" aria-hidden="true">1</p>
            <h3 className="yol-t">Tanlaysiz</h3>
            <p className="yol-d">Do&apos;konda yoki telefon orqali</p>
          </li>

          <li className="yol-step">
            <div className="yol-pic yol-pic--hujjat" aria-hidden="true">
              <svg viewBox="0 0 160 120">
                <rect className="h-pass" x="18" y="14" width="74" height="96" rx="8" />
                <circle className="h-gerb" cx="55" cy="50" r="15" />
                <path className="h-txt" d="M36 82h38M42 92h26" />
                <rect className="h-card" x="68" y="52" width="80" height="52" rx="8" />
                <rect className="h-chip" x="80" y="66" width="16" height="12" rx="2" />
                <path className="h-num" d="M80 92h56" />
              </svg>
            </div>
            <p className="yol-n" aria-hidden="true">2</p>
            <h3 className="yol-t">Pasport va karta</h3>
            <p className="yol-d">Kafil va ma&apos;lumotnoma kerak emas</p>
          </li>

          <li className="yol-step">
            <div className="yol-pic yol-pic--uy" aria-hidden="true">
              <svg viewBox="0 0 160 120">
                <path className="u-uy" d="M30 112V52L80 16l50 36v60Z" />
                <rect className="u-deraza" x="66" y="58" width="28" height="26" rx="2" />
                <path className="u-ramka" d="M80 58v26M66 71h28" />
              </svg>
            </div>
            <p className="yol-n" aria-hidden="true">3</p>
            <h3 className="yol-t">Uyingizga olib boramiz</h3>
            <p className="yol-d">
              {site.freeDeliveryArea}da yetkazish va o&apos;rnatish bepul
            </p>
          </li>

          </ol>
        </div>

        <div className="tqv" data-m="">
          <div className="tqv-head">
            <p className="tqv-big">
              <span className="tqv-bugun">Bugun</span> mahsulot uyda.
            </p>
            <p className="tqv-sub">To&apos;lov esa {oy} oyga bo&apos;linadi.</p>
          </div>
          <ol className="tqv-row" aria-label={`To'lov ${oy} oygacha bo'linadi`}>
            <li className="tqv-uy">
              <svg viewBox="0 0 40 40" aria-hidden="true">
                <path d="M4 37V16L20 4l16 12v21Z" />
                <rect x="15" y="20" width="10" height="10" rx="1" />
              </svg>
              <span>Bugun</span>
            </li>
            {months.map((m) => (
              <li key={m} className="tqv-oy" style={{ ["--i" as string]: m }}>
                <span className="tqv-win" aria-hidden="true" />
                <span>{m}-oy</span>
              </li>
            ))}
          </ol>
          <Link href="/muddatli-tolov" className="u-link">
            Muddatli to&apos;lov shartlari
          </Link>
        </div>
      </div>
    </section>
  );
}
