import { site } from "@/data/site";
import { branches } from "@/data/branches";

/**
 * "Nega Hamkor?" — hero'dan keyingi ishonch qatori.
 *
 * To'rtta raqam, hammasi tasdiqlangan: mahsulot soni va muddat admin
 * sozlamasidan, filial soni ro'yxatdan sanaladi, baho — Yandex Xaritadagi
 * haqiqiy karta (data/site.ts > reviews). O'ylab topilgan "1000+ mamnun
 * mijoz" o'rniga shu: uni har kim o'zi tekshira oladi.
 */
export function Trust() {
  const { reviews } = site;
  const rating = reviews.rating.toFixed(1).replace(".", ",");
  const cities = Array.from(new Set(branches.map((b) => b.city))).join(", ");
  const stats = [
    { value: site.facts.productCount, label: "Mahsulot", note: "Texnika, tilla, mebel va skuterlar" },
    { value: `${site.facts.installmentMonthsMax} oy`, label: "Muddatli to'lov", note: "Pasport va plastik karta bilan" },
    { value: `${site.facts.branchCount} ta`, label: "Filial", note: cities },
  ];

  return (
    <section id="nega-hamkor" aria-labelledby="trust-title" className="bg-ground-2 py-16 sm:py-20">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div data-reveal className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
          <div>
            <p className="kicker">Ishonch</p>
            <h2 id="trust-title" className="display mt-3 text-4xl sm:text-5xl">
              Nega Hamkor?
            </h2>
          </div>
          <p className="max-w-md leading-relaxed text-ink-2">
            Andijon viloyatidagi do&apos;konlar tarmog&apos;i — hammasi bir joyda, qulay shartlarda.
          </p>
        </div>

        <ul data-reveal className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-5">
          {stats.map((s) => (
            <li key={s.label} className="card p-6 sm:p-7">
              <p className="numeral text-5xl leading-none text-purple sm:text-6xl">{s.value}</p>
              <p className="mt-4 font-semibold text-ink">{s.label}</p>
              <p className="mt-1 text-sm text-ink-3">{s.note}</p>
            </li>
          ))}
          <li>
            <a
              href={reviews.readUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="card card-hover group block h-full p-6 sm:p-7"
            >
              <p className="numeral flex items-baseline gap-2 text-5xl leading-none text-purple sm:text-6xl">
                {rating}
                <span className="text-3xl text-yellow" aria-hidden="true">
                  ★
                </span>
              </p>
              <p className="mt-4 font-semibold text-ink">Mijozlar bahosi</p>
              <p className="mt-1 text-sm text-ink-3">
                {reviews.source}da {reviews.count} ta baho{" "}
                <span className="nudge text-purple" aria-hidden="true">
                  &rarr;
                </span>
              </p>
            </a>
          </li>
        </ul>
      </div>
    </section>
  );
}
