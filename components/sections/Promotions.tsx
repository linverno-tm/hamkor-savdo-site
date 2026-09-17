import { content } from "@/lib/content";
import { tashkentToday, uploadedImage } from "@/lib/images";

/**
 * Aksiyalar — admin paneldan. Build paytida muddati o'tganlari tashlab
 * yuboriladi; build'lar orasida esa quyidagi kichik skript sanaga qarab
 * yashiradi/ko'rsatadi. GitHub Actions har tunda saytni qayta yig'adi, shuning
 * uchun JavaScript'siz brauzerda ham ko'pi bilan bir kun kechikadi.
 */
const SHOW_SCRIPT = `(function(){
var t=new Date(Date.now()+18e6).toISOString().slice(0,10);
var s=document.getElementById('aksiyalar'); if(!s) return;
var any=false;
s.querySelectorAll('[data-promo]').forEach(function(el){
  var a=el.getAttribute('data-starts'), b=el.getAttribute('data-ends');
  var on=(!a||t>=a)&&(!b||t<=b);
  el.hidden=!on; if(on) any=true;
});
s.hidden=!any;
})();`;

export function Promotions() {
  const today = tashkentToday();
  const promos = content.promotions.filter((p) => !p.endsAt || p.endsAt >= today);
  if (promos.length === 0) return null;
  const anyActive = promos.some((p) => !p.startsAt || p.startsAt <= today);

  return (
    <section
      id="aksiyalar"
      aria-labelledby="promo-title"
      className="bg-ground py-14 sm:py-20"
      hidden={!anyActive}
    >
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <p className="kicker">Aksiyalar</p>
        <h2 id="promo-title" className="display mt-3 text-3xl sm:text-4xl">
          Hozir amalda
        </h2>

        <ul className="mt-8 grid gap-5 md:grid-cols-2">
          {promos.map((p) => {
            const img = uploadedImage(p.image);
            const upcoming = !!p.startsAt && p.startsAt > today;
            return (
              <li
                key={p.id}
                data-promo
                data-starts={p.startsAt || undefined}
                data-ends={p.endsAt || undefined}
                hidden={upcoming}
                className="card flex flex-col overflow-hidden"
              >
                {img ? (
                  <img
                    src={img.src}
                    srcSet={`${img.srcSmall} 480w, ${img.src} 960w`}
                    sizes="(max-width: 768px) 100vw, 50vw"
                    width={960}
                    height={720}
                    alt={p.title}
                    loading="lazy"
                    decoding="async"
                    className="block aspect-[16/9] w-full object-cover"
                  />
                ) : null}
                <div className="flex flex-1 flex-col p-7">
                  <h3 className="display text-2xl">{p.title}</h3>
                  {p.body ? (
                    <p className="mt-3 whitespace-pre-line leading-relaxed text-ink-2">{p.body}</p>
                  ) : null}
                  {p.endsAt ? (
                    <p className="mt-4 text-sm font-semibold text-purple">
                      {p.endsAt.split("-").reverse().join(".")} gacha
                    </p>
                  ) : null}
                  <a href="#ariza" className="btn btn-primary mt-6 self-start">
                    Ariza qoldirish
                  </a>
                </div>
              </li>
            );
          })}
        </ul>
      </div>
      <script data-keep dangerouslySetInnerHTML={{ __html: SHOW_SCRIPT }} />
    </section>
  );
}
