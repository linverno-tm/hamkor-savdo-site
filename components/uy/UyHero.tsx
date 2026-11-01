import { site } from "@/data/site";
import { UyTezForma } from "@/components/uy/UyTezForma";

/**
 * Bosh qism — g'oya: «Uyingizga hozir — to'lov keyin».
 *
 * Sayt bitta vizual metafora atrofida qurilgan: tomli uy shakli (uy = mahsulot
 * birinchi kundanoq sizda) va sariq "deraza chirog'i" (brendning ikkinchi
 * kalit rangi #dfd01f). Bu shakl bo'limdan bo'limga takrorlanadi: bu yerda
 * xona surati, yo'nalishlarda bo'lim suratlari, to'lov taqvimida yonadigan
 * derazalar.
 *
 * LCP qoidasi: sarlavha va matn birinchi kadrdanoq ko'rinadi — hech qanday
 * kirish animatsiyasi ularni yashirmaydi (01.11: telefonda 4 s gacha matn
 * ko'rinmagan). Harakat faqat bezakda: uy ortidagi sariq soya bir marta suriladi.
 */
export function UyHero() {
  const oy = site.facts.installmentMonthsMax;
  return (
    <section id="hero" aria-labelledby="hero-title" className="uh">
      <div className="u-wrap uh-grid">
        <h1 id="hero-title" className="uh-title">
          <span className="uh-l">Uyingizga hozir.</span>
          <span className="uh-l uh-l2">To&apos;lov — keyin.</span>
        </h1>

        <p className="uh-lead">
          Texnika, mebel va tilla — pasport va karta bilan, {oy} oygacha bo&apos;lib to&apos;laysiz.
        </p>

        <div className="uh-cta">
          <a href="#ariza" className="btn btn-yellow">
            Ariza qoldirish
          </a>
          <a href={`tel:${site.phone}`} className="btn uh-call">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path
                d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinejoin="round"
              />
            </svg>
            {site.phoneDisplay}
          </a>
        </div>

        <div className="uh-form-wrap">
          <UyTezForma />
        </div>

        <figure className="uh-uy">
          <div className="uh-uy-frame">
            <img
              src="/uy/xona-1000.webp"
              srcSet="/uy/xona-640.webp 640w, /uy/xona-1000.webp 1000w, /uy/xona-1400.webp 1400w"
              sizes="(min-width: 1024px) 46vw, 92vw"
              width={1000}
              height={732}
              alt="Yorug' mehmonxona: devorda televizor, yog'och komod va yumshoq kreslo"
              fetchPriority="high"
            />
          </div>
          <span className="uh-badge" aria-hidden="true">
            <span>Pasport</span>
            <span>+ karta</span>
          </span>
          <figcaption className="sr-only">Mahsulot birinchi kundanoq uyingizda.</figcaption>
        </figure>

        <ul className="uh-facts" aria-label="Asosiy shartlar">
          <li>
            <svg viewBox="0 0 40 40" aria-hidden="true">
              <rect x="5" y="9" width="30" height="24" rx="3" />
              <path d="M5 16h30M11 4v8M29 4v8" />
              <text x="20" y="29.5" textAnchor="middle">{oy}</text>
            </svg>
            <span>
              <b>{oy} oygacha</b> muddatli to&apos;lov
            </span>
          </li>
          <li>
            <svg viewBox="0 0 40 40" aria-hidden="true">
              <path d="M3 27V12h20v15M23 17h8l6 6v4H23" />
              <circle cx="11" cy="29" r="3.5" />
              <circle cx="30" cy="29" r="3.5" />
            </svg>
            <span>
              <b>Bepul</b> yetkazish va o&apos;rnatish — {site.freeDeliveryArea}
            </span>
          </li>
          <li>
            <svg viewBox="0 0 40 40" aria-hidden="true">
              <path d="M5 35V17L20 6l15 11v18Z" />
              <path d="M15 35V24h10v11" />
            </svg>
            <span>
              <b>{site.facts.branchCount} ta filial</b>, {site.facts.productCount} mahsulot
            </span>
          </li>
        </ul>
      </div>
    </section>
  );
}
