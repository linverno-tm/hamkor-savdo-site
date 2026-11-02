import { site } from "@/data/site";
import { content, Rich } from "@/lib/content";
import { lavhalar } from "@/lib/kanal";
import { Yukl } from "@/components/ui/Yukl";

/**
 * Ishonch — o'ylab topilgan sharh yo'q, faqat tekshirsa bo'ladigan narsalar:
 * Yandex Xaritadagi baho, Instagram obunachilari, rasmiy kanaldagi mijoz
 * lavhalari (videolar) va filiallardagi haqiqiy suratlar.
 *
 * `id="biz-haqimizda"` — menyudagi "Biz haqimizda" shu yerga olib keladi;
 * admin paneldagi "Biz haqimizda" matni ham shu yerda (yig'ilgan holda).
 */
const PHOTOS = [
  { src: "/filiallar/shahrixon-ozodbek/mijoz-1-480.webp", alt: "Shahrixon filialida sotuvchi mijozlarga mahsulot ko'rsatmoqda" },
  { src: "/filiallar/andijon-amir-temur/jamoa-1-480.webp", alt: "Andijon filiali jamoasi rasmiylashtirish stolida" },
  { src: "/filiallar/asaka-umid/mijoz-1-480.webp", alt: "Asaka filialida mijoz telefon tanlamoqda" },
];

export function UyIshonch() {
  const { reviews } = site;
  const rating = reviews.rating.toFixed(1).replace(".", ",");
  const full = Math.floor(reviews.rating);
  const items = lavhalar();

  return (
    <section id="biz-haqimizda" aria-labelledby="ish-title" className="uy-sec">
      <div className="u-wrap">
        <h2 id="ish-title" className="u-h2">
          Haqiqiy do&apos;kon, haqiqiy mijozlar
        </h2>

        <div className="ish" id="sharhlar">
          <a href={reviews.readUrl} target="_blank" rel="noopener noreferrer" className="ish-baho" data-m="">
            <span className="ish-num">{rating}</span>
            <span className="ish-stars" aria-label={`5 dan ${rating}`}>
              {Array.from({ length: 5 }, (_, i) => (
                <span key={i} className={i < full ? "is-on" : i === full ? "is-half" : ""} style={{ ["--s" as string]: i }}>
                  ★
                </span>
              ))}
            </span>
            <span className="ish-cap">
              {reviews.source}da {reviews.count} ta baho
            </span>
          </a>

          <a href={site.instagramUrl} target="_blank" rel="noopener noreferrer" className="ish-ig" data-m="">
            <span className="ish-num">{site.facts.instagramFollowers}</span>
            <span className="ish-cap">Instagram&apos;da kuzatadi · @{site.instagram}</span>
          </a>

          <ul className="ish-foto" data-m="" data-m-stagger>
            {PHOTOS.map((p) => (
              <li key={p.src} className="yk">
                <Yukl />
                <img src={p.src} width={480} height={360} alt={p.alt} loading="lazy" decoding="async" />
              </li>
            ))}
          </ul>
        </div>

        {/* Rasmiy kanaldagi mijoz lavhalari (tools/kanal-lavhalar.mjs, har kecha).
            Bosilganda Telegram o'yinchisi shu sahifada ochiladi (data-lavha). */}
        {items.length >= 3 ? (
          <div className="ish-lv">
            <p className="ish-lv-h">
              Mijozlar lavhalari — rasmiy Telegram kanalidan{" "}
              <a href={site.telegramUrl} target="_blank" rel="noopener noreferrer">
                @{site.telegram}
              </a>
            </p>
            <ul className="ish-rail" aria-label="Rasmiy kanaldagi mijoz lavhalari">
              {items.map((l) => (
                <li key={l.id}>
                  <a href={l.url} target="_blank" rel="noopener noreferrer" data-lavha={l.url} className="ish-lavha yk">
                    <Yukl />
                    <img
                      src={`${l.image}-480.webp`}
                      width={180}
                      height={320}
                      alt=""
                      loading="lazy"
                      decoding="async"
                    />
                    <span className="ish-play" aria-hidden="true">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5Z" />
                      </svg>
                    </span>
                    <span className="ish-lv-t">{l.title}</span>
                  </a>
                </li>
              ))}
            </ul>
          </div>
        ) : null}

        <details className="ish-about">
          <summary>Biz haqimizda batafsil</summary>
          {content.texts.aboutParagraphs.map((p, i) => (
            <p key={i}>
              <Rich text={p} strongClass="font-semibold" />
            </p>
          ))}
        </details>
      </div>
    </section>
  );
}
