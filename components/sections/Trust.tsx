import { site } from "@/data/site";
import { branches } from "@/data/branches";
import { CountUp } from "@/components/ui/CountUp";
import { SplitWords } from "@/components/ui/SplitWords";

/**
 * "Nega Hamkor?" — hero'dan keyingi ishonch qatori.
 *
 * To'rtta raqam, hammasi tasdiqlangan: mahsulot soni va muddat admin
 * sozlamasidan, filial soni ro'yxatdan sanaladi, baho — Yandex Xaritadagi
 * haqiqiy karta (data/site.ts > reviews).
 *
 * Ko'rinish: turli o'lchamdagi to'rt plitka (bento). Eng kattasi —
 * mahsulot soni, binafsha; muddat — sariq. Raqamlar ekranga kirganda
 * noldan sanaladi (`data-count`, lib/motion.ts), server esa yakuniy
 * qiymatni yozadi.
 */
export function Trust() {
  const { reviews } = site;
  const rating = reviews.rating.toFixed(1).replace(".", ",");
  const cities = Array.from(new Set(branches.map((b) => b.city))).join(", ");

  return (
    <section id="nega-hamkor" aria-labelledby="trust-title" className="sec trust">
      <div className="wrap">
        <div data-m className="trust-head">
          <div>
            <p className="eyebrow">Ishonch</p>
            <h2 id="trust-title" className="sh-title">
              <SplitWords text="Nega Hamkor?" />
            </h2>
          </div>
          <p className="trust-intro">
            Andijon viloyatidagi do&apos;konlar tarmog&apos;i — hammasi bir joyda, qulay shartlarda.
          </p>
        </div>

        <ul data-m="" data-m-stagger className="bento">
          <li className="tile tile-purple tile-big">
            <p className="tile-num">
              <CountUp value={site.facts.productCount} />
            </p>
            <div>
              <p className="tile-label">Mahsulot</p>
              <p className="tile-note">Texnika, tilla, mebel va skuterlar</p>
            </div>
          </li>
          <li className="tile tile-yellow">
            <p className="tile-num">
              <CountUp value={`${site.facts.installmentMonthsMax} oy`} />
            </p>
            <div>
              <p className="tile-label">Muddatli to&apos;lov</p>
              <p className="tile-note">Pasport va plastik karta bilan</p>
            </div>
          </li>
          <li className="tile">
            <p className="tile-num">
              <CountUp value={`${site.facts.branchCount} ta`} />
            </p>
            <div>
              <p className="tile-label">Filial</p>
              <p className="tile-note">{cities}</p>
            </div>
          </li>
          <li className="tile-link-wrap">
            <a href={reviews.readUrl} target="_blank" rel="noopener noreferrer" className="tile tile-link group">
              <p className="tile-num">
                {rating}
                <span className="tile-star" aria-hidden="true">
                  ★
                </span>
              </p>
              <div>
                <p className="tile-label">Mijozlar bahosi</p>
                <p className="tile-note">
                  {reviews.source}da {reviews.count} ta baho{" "}
                  <span className="nudge" aria-hidden="true">
                    &rarr;
                  </span>
                </p>
              </div>
            </a>
          </li>
        </ul>
      </div>
    </section>
  );
}
