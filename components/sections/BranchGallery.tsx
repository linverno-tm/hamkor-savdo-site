import type { Photo } from "@/lib/photos";

/**
 * A branch's own photography, laid out editorially rather than as a uniform
 * grid: the storefront runs full width as a wide establishing shot, the
 * interiors follow beneath it. That order matches how someone actually reads
 * the place — outside first, then in.
 *
 * Clicking a photo opens it full screen. The lightbox is pure CSS (`:target`),
 * so it costs no JavaScript and still works while the framework bundle is
 * downloading — the same rule the rest of this site follows.
 *
 * Plain <img> with an explicit srcset rather than next/image: this is a static
 * export on shared hosting, so there is no image optimiser at runtime;
 * `tools/rasm-tayyorlash.py` already wrote the two sizes at build time.
 * width/height are declared so the browser reserves the space before the file
 * arrives — on a slow connection that is the difference between a page that
 * settles and one that jumps while it loads.
 */
const COLS = 3;

/**
 * How many columns each photo after the establishing shot should take.
 *
 * The base rhythm is 2+1, then 1+2, repeating: every row still adds up to
 * three columns, so the grid never leaves a hole mid-page, but no two
 * neighbours share a shape. That difference — varied frames rather than
 * identical thumbnails — is what separates a page of photographs from a
 * folder of files.
 *
 * The one exception is the final photo. If the rhythm leaves it starting a row
 * on its own, it stretches to the full width instead of trailing a gap beside
 * it, which reads as an unfinished row rather than a deliberate ending.
 */
function layoutSpans(count: number): number[] {
  const spans: number[] = [];
  let used = 0;

  for (let i = 0; i < count; i++) {
    let span = i % 4 === 0 || i % 4 === 3 ? 2 : 1;
    if (used + span > COLS) used = 0; // yangi qator boshlandi
    if (i === count - 1 && used === 0 && span < COLS) span = COLS;

    spans.push(span);
    used += span;
    if (used >= COLS) used = 0;
  }

  return spans;
}

/* Tailwind sinf nomlarini shablon orqali yasab bo'lmaydi — u manba matnidan
   to'liq nomlarni qidiradi. Shuning uchun ular shu yerda to'liq yozilgan.
   Telefonda ustun ikkita, shuning uchun 3 ham 2 ham butun kenglikni oladi. */
const SPAN_CLASS: Record<number, string> = {
  1: "lg:col-span-1",
  2: "col-span-2 lg:col-span-2",
  3: "col-span-2 lg:col-span-3",
};

const SPAN_SIZES: Record<number, string> = {
  1: "(max-width: 640px) 50vw, 33vw",
  2: "(max-width: 1024px) 100vw, 66vw",
  3: "100vw",
};

export function BranchGallery({
  photos,
  city,
  slug,
  heading,
}: {
  photos: Photo[];
  city: string;
  slug: string;
  heading?: string;
}) {
  if (photos.length === 0) return null;

  const [lead, ...rest] = photos;
  const id = (i: number) => `foto-${slug}-${i}`;
  const spans = layoutSpans(rest.length);

  return (
    <section id="suratlar" aria-labelledby="gallery-title" className="bg-ground py-16 sm:py-20">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <p className="kicker">Haqiqiy do&apos;kon</p>
        <h2 id="gallery-title" className="display mt-3 text-3xl sm:text-4xl">
          {heading ?? `${city} filiali — suratlarda`}
        </h2>
        <p className="mt-3 text-sm text-ink-3">
          Kattalashtirib ko&apos;rish uchun suratni bosing.
        </p>

        <div data-reveal className="mt-10">
          {/* Establishing shot */}
          <a href={`#${id(0)}`} className="photo photo-lead group block">
            <img
              src={lead.src}
              srcSet={`${lead.srcSmall} 480w, ${lead.src} 960w`}
              sizes="(max-width: 1024px) 100vw, 1200px"
              width={960}
              height={720}
              alt={lead.alt}
              loading="eager"
              decoding="async"
            />
            <span className="photo-cap">{lead.label}</span>
          </a>

          {rest.length > 0 ? (
            <ul className="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-3">
              {rest.map((p, i) => {
                const span = spans[i];
                const shape = span === 3 ? " photo-full" : span === 2 ? " photo-wide" : "";
                return (
                  <li key={p.src} className={SPAN_CLASS[span]}>
                    <a href={`#${id(i + 1)}`} className={`photo group block${shape}`}>
                      <img
                        src={p.src}
                        srcSet={`${p.srcSmall} 480w, ${p.src} 960w`}
                        sizes={SPAN_SIZES[span]}
                        width={960}
                        height={720}
                        alt={p.alt}
                        loading="lazy"
                        decoding="async"
                      />
                      <span className="photo-cap">{p.label}</span>
                    </a>
                  </li>
                );
              })}
            </ul>
          ) : null}
        </div>
      </div>

      {/* Lightboxes. Hidden until their id is the URL fragment. */}
      {photos.map((p, i) => (
        <div key={p.src} id={id(i)} className="lightbox" role="dialog" aria-label={p.alt}>
          <a href="#suratlar" className="lightbox-close" aria-label="Yopish">
            &times;
          </a>
          <a href="#suratlar" className="lightbox-scrim" tabIndex={-1} aria-hidden="true" />
          <figure className="lightbox-figure">
            <img src={p.src} alt={p.alt} width={960} height={720} decoding="async" />
            <figcaption>
              {p.label} — {city}
            </figcaption>
          </figure>
        </div>
      ))}
    </section>
  );
}
