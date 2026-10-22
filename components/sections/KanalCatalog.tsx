import { site } from "@/data/site";
import { kanalItems } from "@/lib/kanal";
import { SectionHeading } from "@/components/ui/SectionHeading";

/** "2026-10-17" -> "17.10" */
const shortDate = (d: string) => d.split("-").reverse().slice(0, 2).join(".");

/**
 * Telegram kanalidagi narxli takliflar — sayt har kecha qayta yig'ilganda
 * yangilanadi (tools/kanal-katalog.mjs). Taklif bo'lmasa, bo'lim chiqmaydi.
 *
 * Har kartada "Shuni so'rash": bosilganda pastdagi ariza formasiga
 * o'tadi va "Nima qiziqtiradi?" maydoniga taklif nomi va post havolasi
 * yozilib qoladi (`data-ask`, lib/motion.ts). JavaScript'siz — oddiy
 * `#ariza` havolasi.
 *
 * Narx hisoblanmaydi: postdagi narx qatori o'zgarishsiz ko'rsatiladi.
 */
export function KanalCatalog() {
  const items = kanalItems();
  if (items.length === 0) return null;

  return (
    <section id="takliflar" aria-labelledby="kanal-title" className="sec kanal">
      <div className="wrap">
        <div className="kanal-head">
          <SectionHeading
            id="kanal-title"
            kicker="Telegram kanalimizdan"
            title="Hozirgi takliflar"
            lead="Kanalga joylangan narxli takliflar — sayt har kuni yangilanadi."
          />
          <a href={site.telegramUrl} target="_blank" rel="noopener noreferrer" className="btn btn-outline shrink-0">
            Kanalni ochish
          </a>
        </div>

        <ul data-m="" data-m-stagger className="kanal-grid">
          {items.map((it) => (
            <li key={it.id} className="kanal-card">
              <a href={it.url} target="_blank" rel="noopener noreferrer" className="kanal-media" tabIndex={-1} aria-hidden="true">
                {it.image ? (
                  <img
                    src={`${it.image}-960.webp`}
                    srcSet={`${it.image}-480.webp 480w, ${it.image}-960.webp 960w`}
                    sizes="(max-width: 640px) 85vw, (max-width: 1024px) 45vw, 30vw"
                    width={960}
                    height={1200}
                    alt=""
                    loading="lazy"
                    decoding="async"
                  />
                ) : null}
                <span className="kanal-date">{shortDate(it.date)}</span>
              </a>
              <div className="kanal-body">
                <h3 className="kanal-title">{it.title}</h3>
                <ul className="kanal-prices">
                  {it.prices.map((p) => (
                    <li key={p}>{p}</li>
                  ))}
                </ul>
                {it.body ? <p className="kanal-text">{it.body}</p> : null}
                <div className="kanal-actions">
                  <a href="#ariza" data-ask={`${it.title} — ${it.url}`} className="btn btn-primary">
                    Shuni so&apos;rash
                  </a>
                  <a href={it.url} target="_blank" rel="noopener noreferrer" className="kanal-tg">
                    Telegram&apos;da ko&apos;rish
                  </a>
                </div>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </section>
  );
}
