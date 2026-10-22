import Link from "next/link";
import { site } from "@/data/site";
import { SplitWords } from "@/components/ui/SplitWords";

/**
 * Muddatli to'lov — sahifaning asosiy va'dasi, shuning uchun eng "og'ir"
 * bo'lim: to'q binafsha, to'liq kenglikda.
 *
 * Kompyuterda chap ustun (badge, sarlavha, tugma) joyida turadi, o'ngdagi
 * uch karta aylantirilganda bir-birining ustiga taxlanadi (sticky). Orqada
 * muddat raqami katta kontur bo'lib suzadi. Telefonda — oddiy ustun.
 *
 * `id="muddatli-tolov"` saqlanadi: filial sahifalari `/#muddatli-tolov` ga
 * havola beradi.
 */
export function Installment() {
  const M = site.facts.installmentMonthsMax;
  const points = [
    { title: "Pasport + karta", text: "Kafil ham, ma'lumotnoma ham kerak emas.", icon: "id" },
    { title: "Bugun olib ketasiz", text: "Mahsulot hozir sizniki, to'lov oylarga bo'linadi.", icon: "box" },
    { title: "Yo'q mahsulot ham", text: "Boshqa joyda ko'rganingizni ham rasmiylashtiramiz.", icon: "search" },
  ];

  return (
    <section id="muddatli-tolov" aria-labelledby="installment-title" className="inst on-purple scroll-mt-20 text-white">
      <div className="inst-bg" aria-hidden="true">
        <span className="inst-num numeral">{M}</span>
        <span className="inst-glow" />
      </div>

      <div className="wrap inst-grid">
        <div className="inst-side">
          <div data-m className="inst-sticky">
            <p className="inst-badge">{M} oygacha muddatli to&apos;lov</p>
            <h2 id="installment-title" className="sh-title inst-title">
              <SplitWords text="Kerakli mahsulotni hozir oling, to'lovni qulay amalga oshiring" />
            </h2>
            <p className="inst-lead">Pasport va plastik karta orqali xaridni rasmiylashtirish imkoniyati.</p>
            <Link href="/muddatli-tolov" className="btn btn-yellow btn-glow mt-9">
              Muddatli to&apos;lov haqida
              <span className="nudge" aria-hidden="true">&rarr;</span>
            </Link>
          </div>
        </div>

        <ul className="inst-stack">
          {points.map((p, i) => (
            <li key={p.title} className="inst-card" style={{ ["--i" as string]: i }}>
              <div data-m="up" className="inst-card-in">
                <span className="inst-ico" aria-hidden="true">
                  <InstIcon name={p.icon} />
                </span>
                <span className="inst-idx numeral" aria-hidden="true">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <p className="inst-card-title">{p.title}</p>
                <p className="inst-card-text">{p.text}</p>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </section>
  );
}

function InstIcon({ name }: { name: string }) {
  const common = {
    width: 28,
    height: 28,
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: 1.8,
    strokeLinecap: "round" as const,
    strokeLinejoin: "round" as const,
  };
  if (name === "id")
    return (
      <svg {...common}>
        <rect x="3" y="5" width="18" height="14" rx="3" />
        <circle cx="9" cy="11" r="2" />
        <path d="M6.5 16c.6-1.4 1.5-2 2.5-2s1.9.6 2.5 2M14 10h4M14 13.5h3" />
      </svg>
    );
  if (name === "box")
    return (
      <svg {...common}>
        <path d="M3.5 7.5 12 3l8.5 4.5v9L12 21l-8.5-4.5v-9Z" />
        <path d="M3.5 7.5 12 12l8.5-4.5M12 12v9" />
      </svg>
    );
  return (
    <svg {...common}>
      <circle cx="11" cy="11" r="6.5" />
      <path d="m20 20-4.2-4.2" />
    </svg>
  );
}
