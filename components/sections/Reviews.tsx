import { site } from "@/data/site";

/**
 * "Mijozlarimiz nima deydi?" — o'ylab topilgan sharh kartochkalari o'rniga
 * haqiqiy manbalar: Yandex Xaritadagi baho (o'qish va yozish havolalari bilan)
 * va mijozlar xaridi chiqadigan Telegram kanal. Har bir raqamni o'quvchi
 * o'zi tekshira oladi — ishonch shundan.
 */
export function Reviews() {
  const { reviews } = site;
  const rating = reviews.rating.toFixed(1).replace(".", ",");
  // 4,5 ni 5 yulduz qilib ko'rsatmaslik uchun pastga yaxlitlanadi.
  const full = Math.floor(reviews.rating);

  return (
    <section id="sharhlar" aria-labelledby="reviews-title" className="bg-ground-2 py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div data-reveal className="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
          <div className="card flex flex-col justify-between gap-8 p-7 sm:p-10">
            <div>
              <p className="kicker">Sharhlar</p>
              <h2 id="reviews-title" className="display mt-3 text-3xl sm:text-5xl">
                Mijozlarimiz nima deydi?
              </h2>
              <div className="mt-8 flex flex-wrap items-end gap-x-6 gap-y-3">
                <p className="numeral text-7xl leading-none text-purple sm:text-8xl">{rating}</p>
                <div className="pb-1">
                  <p className="text-2xl tracking-[0.15em] text-yellow" aria-label={`5 dan ${rating}`}>
                    {"★".repeat(full)}
                    <span className="text-line">{"★".repeat(5 - full)}</span>
                  </p>
                  <p className="mt-1 text-sm text-ink-2">
                    {reviews.source}da {reviews.count} ta baho
                  </p>
                </div>
              </div>
            </div>
            <div className="flex flex-wrap gap-3">
              <a href={reviews.readUrl} target="_blank" rel="noopener noreferrer" className="btn btn-primary btn-lift">
                Sharhlarni o&apos;qish
                <span className="nudge" aria-hidden="true">&rarr;</span>
              </a>
              <a href={reviews.writeUrl} target="_blank" rel="noopener noreferrer" className="btn btn-outline btn-lift">
                Sharh qoldirish
              </a>
            </div>
          </div>

          <div className="on-purple flex flex-col justify-between gap-8 rounded-[24px] bg-purple p-7 text-white sm:p-10">
            <div>
              <p className="kicker">Jonli lavhalar</p>
              <h3 className="display mt-3 text-2xl sm:text-3xl">Haqiqiy mijozlar, haqiqiy xaridlar</h3>
              <p className="mt-4 leading-relaxed text-on-purple-2">
                Telegram&apos;dagi @{site.telegramCustomers} kanalida har kuni yangi xaridlar va mijozlarimiz
                bilan lavhalar chiqib turadi.
              </p>
            </div>
            <a
              href={site.telegramCustomersUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-yellow btn-lift self-start"
            >
              Kanalni ochish
              <span className="nudge" aria-hidden="true">&rarr;</span>
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}
