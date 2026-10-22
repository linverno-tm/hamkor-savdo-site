import { site } from "@/data/site";
import { SplitWords } from "@/components/ui/SplitWords";
import { lavhalar } from "@/lib/kanal";

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
  const items = lavhalar();

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

      {/* Rasmiy kanaldagi mijoz lavhalari — har kecha yangilanadi
          (tools/kanal-lavhalar.mjs). Bosilganda Telegram'ning o'z
          o'yinchisi shu yerda ochiladi (lib/motion.ts > data-lavha);
          JavaScript'siz — post Telegram'da ochiladi. */}
      {items.length > 0 ? (
        <div className="lavha-wrap">
          <div className="wrap lavha-head">
            <p className="eyebrow">Rasmiy kanaldan</p>
            <a href={site.telegramUrl} target="_blank" rel="noopener noreferrer" className="lavha-all">
              Hammasi <span aria-hidden="true">&rarr;</span>
            </a>
          </div>
          <ul data-m="" data-m-stagger className="lavha-rail" aria-label="Rasmiy kanaldagi mijoz lavhalari">
            {items.map((l) => (
              <li key={l.id} className="lavha-item">
                <a href={l.url} target="_blank" rel="noopener noreferrer" data-lavha={l.url} className="lavha">
                  <img
                    src={`${l.image}-960.webp`}
                    srcSet={`${l.image}-480.webp 480w, ${l.image}-960.webp 960w`}
                    sizes="(max-width: 640px) 62vw, 260px"
                    width={960}
                    height={1280}
                    alt=""
                    loading="lazy"
                    decoding="async"
                    className="lavha-img"
                  />
                  <span className="lavha-shade" aria-hidden="true" />
                  <span className="lavha-play" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5Z" />
                    </svg>
                  </span>
                  <span className="lavha-body">
                    <span className="lavha-date">{l.date.split("-").reverse().join(".")}</span>
                    <span className="lavha-title">{l.title}</span>
                  </span>
                </a>
              </li>
            ))}
          </ul>
        </div>
      ) : null}
    </section>
  );
}
