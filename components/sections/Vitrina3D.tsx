import { SectionHeading } from "@/components/ui/SectionHeading";
import { VITRINA, VITRINA_KAT, type VitrinaKat } from "@/lib/vitrina";

/**
 * 3D vitrina — real 3D modellar (Sketchfab CC BY, Poly Haven CC0), har
 * tomondan aylantirib ko'riladi.
 * Egasi 26.10: "animatsiyalar real chiqsin, internetdagi tayyor eng
 * yaxshilaridan foydalan".
 *
 * Ishlash tartibi:
 *  - Tanlov — CSS radio tugmalari (JavaScript'siz va kirillcha nusxada ham
 *    ishlaydi; strelkalar bilan ham). JS'siz — tanlangan mahsulotning rasmi.
 *  - 3D — o'zimizning yengil three.js yadrosi (tools/hero3d/hamkor3d.js): sahifa
 *    ochilib, brauzer bo'shaganda tayyorlanadi; tanlov o'zgarsa model almashadi.
 *    model-viewer olib tashlandi: Windows'da skroll paytida 2 s qotirardi.
 *  - "Narxini so'rash" — pastdagi ariza formasiga mahsulot nomini yozadi
 *    (data-ask, lib/motion.ts).
 */
const T = {
  uz: {
    kicker: "3D vitrina",
    title: "Har tomondan ko'ring",
    lead: "Barmoq yoki sichqoncha bilan aylantirib, har tomonini ko'ring.",
    ask: "Narxini so'rash",
    askNote: (n: string) => `${n} — narxi va muddatli to'lov shartlari`,
    note: "3D ko'rinish namuna: do'kondagi modellar va ranglar boshqacha bo'lishi mumkin.",
    credits: "3D modellar mualliflari",
    by: "muallif",
  },
  ru: {
    kicker: "3D-витрина",
    title: "Рассмотрите со всех сторон",
    lead: "Поворачивайте пальцем или мышью и рассмотрите товар со всех сторон.",
    ask: "Узнать цену",
    askNote: (n: string) => `${n} — цена и условия рассрочки`,
    note: "3D-модели — пример: модели и цвета в магазине могут отличаться.",
    credits: "Авторы 3D-моделей",
    by: "автор",
  },
};

export function Vitrina3D({ only, lang = "uz" }: { only?: VitrinaKat; lang?: "uz" | "ru" }) {
  const t = T[lang];
  const scope = only ?? "home";
  const items = VITRINA.filter((i) => !only || i.kat === only);
  if (items.length === 0) return null;
  const rid = (id: string) => `vt-${scope}-${id}`;

  /* Har model uchun: tanlansa — sahnada va ma'lumotda ko'rinadi, ro'yxatda belgilanadi. */
  const rules = items
    .map((i) => {
      const r = `#${rid(i.id)}`;
      return (
        `${r}:checked~.vt-stage .vt-model--${i.id},${r}:checked~.vt-side .vt-info--${i.id}{display:flex}` +
        `${r}:checked~.vt-side .vt-item[for="${rid(i.id)}"]{border-color:var(--yellow);background:rgba(255,255,255,.1)}` +
        `${r}:focus-visible~.vt-side .vt-item[for="${rid(i.id)}"]{outline:2px solid var(--yellow);outline-offset:2px}`
      );
    })
    .join("");

  return (
    <section id={only ? undefined : "vitrina"} aria-labelledby={`vt-title-${scope}`} className="vt-sec on-purple text-white">
      <div className="wrap">
        <SectionHeading id={`vt-title-${scope}`} kicker={t.kicker} title={t.title} lead={t.lead} dark />

        <div className="vt" data-3d="">
          {items.map((i, n) => (
            <input
              key={i.id}
              type="radio"
              name={`vt-${scope}`}
              id={rid(i.id)}
              className="vt-r"
              data-kat={i.kat}
              data-id={i.id}
              defaultChecked={n === 0}
              aria-label={i.nom[lang]}
            />
          ))}
          <style>{rules}</style>

          <div className="vt-stage">
            <span className="vt-spot" aria-hidden="true" />
            <span className="vt-pedestal" aria-hidden="true" />
            {/* Rasm — JS'siz va 3D tayyor bo'lguncha; 3D chiqqach so'nadi (app/home-vitrina.css > .is-3d). */}
            {items.map((i) => (
              <div key={i.id} className={`vt-model vt-model--${i.id}`}>
                <img className="vt-poster" src={`/3d/${i.id}.webp`} alt={i.nom[lang]} width={640} height={640} loading="lazy" decoding="async" />
              </div>
            ))}
          </div>

          <div className="vt-side">
            {!only ? (
              <div className="vt-kats">
                {VITRINA_KAT.filter((k) => items.some((i) => i.kat === k.id)).map((k) => (
                  <label key={k.id} htmlFor={rid(items.find((i) => i.kat === k.id)!.id)} className="vt-kat" data-kat={k.id}>
                    {k[lang]}
                  </label>
                ))}
              </div>
            ) : null}

            <div className="vt-items">
              {items.map((i) => (
                <label key={i.id} htmlFor={rid(i.id)} className="vt-item" data-kat={i.kat}>
                  <img src={`/3d/${i.id}.webp`} alt="" width={64} height={64} loading="lazy" decoding="async" />
                  <span>{i.nom[lang]}</span>
                </label>
              ))}
            </div>

            {items.map((i) => (
              <div key={i.id} className={`vt-info vt-info--${i.id}`}>
                <p className="vt-name">{i.nom[lang]}</p>
                <a href="#ariza" className="btn btn-yellow btn-glow" data-ask={t.askNote(i.nom[lang])}>
                  {t.ask}
                  <span className="nudge" aria-hidden="true">
                    &rarr;
                  </span>
                </a>
              </div>
            ))}

            <p className="vt-note">{t.note}</p>
            <details className="vt-credits">
              <summary>{t.credits}</summary>
              <ul translate="no">
                {items.map((i) => (
                  <li key={i.id}>
                    <a href={i.credit.url} target="_blank" rel="noopener noreferrer">
                      {i.credit.title}
                    </a>{" "}
                    — {t.by}{" "}
                    <a href={i.credit.authorUrl} target="_blank" rel="noopener noreferrer">
                      {i.credit.author}
                    </a>
                    , {i.credit.license}
                  </li>
                ))}
              </ul>
            </details>
          </div>
        </div>
      </div>
    </section>
  );
}
