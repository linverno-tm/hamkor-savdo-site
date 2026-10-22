import { site } from "@/data/site";
import { SplitWords } from "@/components/ui/SplitWords";

/**
 * "Mijozlarimiz nima deydi?" — o'ylab topilgan sharh kartochkalari o'rniga
 * haqiqiy manbalar: Yandex Xaritadagi baho (o'qish va yozish havolalari bilan)
 * va mijozlar xaridi chiqadigan Telegram kanal. Har bir raqamni o'quvchi
 * o'zi tekshira oladi — ishonch shundan.
 *
 * Harakat: yulduzlar ekranga kirganda birin-ketin "sakrab" yonadi; Telegram
 * kartasida sekin suzuvchi nur.
 */
export function Reviews() {
  const { reviews } = site;
  const rating = reviews.rating.toFixed(1).replace(".", ",");
  // 4,5 ni 5 yulduz qilib ko'rsatmaslik uchun pastga yaxlitlanadi.
  const full = Math.floor(reviews.rating);

  return (
    <section id="sharhlar" aria-labelledby="reviews-title" className="sec reviews">
      <div className="wrap reviews-grid">
        <div data-m className="review-card">
          <div>
            <p className="eyebrow">Sharhlar</p>
            <h2 id="reviews-title" className="sh-title">
              <SplitWords text="Mijozlarimiz nima deydi?" />
            </h2>
            <div className="review-score">
              <p className="review-num numeral">{rating}</p>
              <div className="pb-2">
                <p className="review-stars" aria-label={`5 dan ${rating}`}>
                  {Array.from({ length: 5 }, (_, i) => (
                    <span key={i} className={i < full ? "is-on" : "is-off"} style={{ ["--s" as string]: i }}>
                      ★
                    </span>
                  ))}
                </p>
                <p className="mt-1 text-sm text-ink-2">
                  {reviews.source}da {reviews.count} ta baho
                </p>
              </div>
            </div>
          </div>
          <div className="flex flex-wrap gap-3">
            <a href={reviews.readUrl} target="_blank" rel="noopener noreferrer" className="btn btn-primary">
              Sharhlarni o&apos;qish
              <span className="nudge" aria-hidden="true">&rarr;</span>
            </a>
            <a href={reviews.writeUrl} target="_blank" rel="noopener noreferrer" className="btn btn-outline">
              Sharh qoldirish
            </a>
          </div>
        </div>

        <div data-m="up" className="on-purple tg-card text-white" style={{ ["--d" as string]: "150ms" }}>
          <span className="tg-orb" aria-hidden="true" />
          <span className="tg-plane" aria-hidden="true">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
              <path d="M21 4 3 11l6 2m12-9-3 16-9-7m12-9L9 13m0 0v6l3-3.5" />
            </svg>
          </span>
          <div className="relative">
            <p className="eyebrow">Jonli lavhalar</p>
            <h3 className="tg-title">Haqiqiy mijozlar, haqiqiy xaridlar</h3>
            <p className="mt-4 leading-relaxed text-on-purple-2">
              Telegram&apos;dagi @{site.telegramCustomers} kanalida har kuni yangi xaridlar va mijozlarimiz
              bilan lavhalar chiqib turadi.
            </p>
          </div>
          <a
            href={site.telegramCustomersUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="btn btn-yellow relative self-start"
          >
            Kanalni ochish
            <span className="nudge" aria-hidden="true">&rarr;</span>
          </a>
        </div>
      </div>
    </section>
  );
}
